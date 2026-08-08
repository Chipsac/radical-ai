<?php

namespace App\Assistant\Tools;

use App\Assistant\Tool;
use App\Models\LeaveRequest;
use App\Models\Task;
use App\Models\User;
use App\Support\Modules;
use Carbon\CarbonImmutable;

/**
 * "What's my schedule today?" — the single most likely question, so it answers
 * in one call rather than making the model assemble it from three.
 */
class MySchedule extends Tool
{
    public function name(): string
    {
        return 'get_schedule';
    }

    public function description(): string
    {
        return 'What someone has on for a date or range: tasks due, and whether '
            .'they or colleagues are on leave. Call this for "what am I doing today", '
            .'"what is due this week", "who is off on Friday".';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'from' => ['type' => 'string', 'format' => 'date', 'description' => 'Start date (YYYY-MM-DD). Defaults to today.'],
                'to' => ['type' => 'string', 'format' => 'date', 'description' => 'End date inclusive. Defaults to the same day as from.'],
                'whole_team' => ['type' => 'boolean', 'description' => 'Include colleagues. Managers only. Default false.'],
            ],
            'additionalProperties' => false,
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->hasModule(Modules::TASKS) || $user->hasModule(Modules::HR);
    }

    public function run(User $user, array $arguments): array
    {
        $from = CarbonImmutable::parse($arguments['from'] ?? 'today')->startOfDay();
        $to = CarbonImmutable::parse($arguments['to'] ?? $from)->endOfDay();

        // Asking for the team does not grant it. An employee gets their own
        // schedule regardless of what the model requested.
        $teamWide = ($arguments['whole_team'] ?? false) && $user->isManager();

        $tasks = $user->hasModule(Modules::TASKS)
            ? Task::with('assignee')
                ->whereBetween('due_date', [$from, $to])
                ->where('status', '!=', 'done')
                ->when(! $teamWide, fn ($q) => $q->where('assignee_id', $user->id))
                ->orderBy('due_date')
                ->limit(50)
                ->get()
                ->map(fn (Task $t) => [
                    'title' => $t->title,
                    'due' => $t->due_date?->toDateString(),
                    'status' => $t->status,
                    'priority' => $t->priority,
                    'assignee' => $t->assignee?->name,
                ])->all()
            : [];

        $leave = $user->hasModule(Modules::HR)
            ? LeaveRequest::with(['employee.user', 'leaveType'])
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $to)
                ->whereDate('end_date', '>=', $from)
                ->when(! $teamWide, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('user_id', $user->id)))
                ->limit(50)
                ->get()
                ->map(fn (LeaveRequest $l) => [
                    'person' => $l->employee?->user?->name,
                    'type' => $l->leaveType?->name,
                    'from' => $l->start_date?->toDateString(),
                    'to' => $l->end_date?->toDateString(),
                ])->all()
            : [];

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'scope' => $teamWide ? 'whole team' : 'just this person',
            'tasks_due' => $tasks,
            'on_leave' => $leave,
        ];
    }
}
