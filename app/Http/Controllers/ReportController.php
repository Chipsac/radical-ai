<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        return view('reports.index', [
            'members' => User::where('organization_id', $request->user()->organization_id)
                ->orderBy('name')->get(),
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'members' => ['required', 'array', 'min:1'],
            'members.*' => ['exists:users,id'],
            'updates_per_task' => ['required', 'integer', 'min:1', 'max:10'],
            'include_completed' => ['nullable', 'boolean'],
            'download' => ['nullable', 'boolean'],
        ]);

        $query = Task::with(['assignee', 'category', 'progressUpdates.user'])
            ->whereIn('assignee_id', $data['members'])
            ->orderBy('assignee_id')
            ->orderByRaw('due_date is null, due_date');

        if (! $request->boolean('include_completed')) {
            $query->where('status', '!=', 'done');
        }

        $tasks = $query->get()->groupBy(fn ($t) => $t->assignee?->name ?? 'Unassigned');

        $html = view('reports.output', [
            'tasksByMember' => $tasks,
            'updatesPerTask' => (int) $data['updates_per_task'],
            'includeCompleted' => $request->boolean('include_completed'),
            'generatedAt' => now(),
            'generatedBy' => $request->user()->name,
            'standalone' => $request->boolean('download'),
        ])->render();

        if ($request->boolean('download')) {
            return response($html)
                ->header('Content-Type', 'text/html; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="task-progress-report-'.now()->format('Y-m-d').'.html"');
        }

        return response($html);
    }
}
