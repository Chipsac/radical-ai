<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $base = Task::query();
        if (! $user->isManager()) {
            $base->where('assignee_id', $user->id);
        }

        return view('tasks.dashboard', [
            'byStatus' => (clone $base)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
            'overdue' => (clone $base)->whereDate('due_date', '<', now())->where('status', '!=', 'done')
                ->with(['assignee', 'category'])->orderBy('due_date')->get(),
            'dueSoon' => (clone $base)->whereBetween('due_date', [now(), now()->addDays(7)])
                ->where('status', '!=', 'done')->with(['assignee', 'category'])->orderBy('due_date')->get(),
            'categories' => TaskCategory::withCount('tasks')->orderBy('name')->get(),
        ]);
    }

    public function board(Request $request)
    {
        $query = Task::with(['assignee', 'category'])->withCount('children');

        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }
        if ($request->filled('assignee')) {
            $query->where('assignee_id', $request->integer('assignee'));
        }
        if ($request->filled('mine')) {
            $query->where('assignee_id', $request->user()->id);
        }

        // Top-level work only by default. Subtasks live on their parent's
        // detail page; showing an unbounded tree flattened onto a kanban
        // board is how boards become unreadable.
        if (! $request->boolean('show_subtasks')) {
            $query->whereNull('parent_id');
        }

        $tasks = $query->orderByRaw('due_date is null, due_date')->get()->groupBy('status');

        return view('tasks.board', [
            'columns' => collect(Task::STATUSES)->mapWithKeys(fn ($s) => [$s => $tasks->get($s, collect())]),
            'categories' => TaskCategory::orderBy('name')->get(),
            'assignees' => User::where('organization_id', $request->user()->organization_id)->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Task $task)
    {
        $this->authorizeTask($task);
        $task->load([
            'assignee', 'category', 'project', 'creator',
            'progressUpdates.user', 'timeEntries',
            'parent', 'childrenRecursive',
        ]);

        return view('tasks.show', [
            'task' => $task,
            'categories' => TaskCategory::orderBy('name')->get(),
            'assignees' => User::where('organization_id', $request->user()->organization_id)
                ->orderBy('name')->get(),
            'ancestors' => $task->ancestors()->reverse()->values(),
        ]);
    }

    /**
     * Break a task down. The new subtask inherits its parent's category and
     * project, because in practice a breakdown almost always belongs to the
     * same bucket of work.
     */
    public function storeSubtask(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
        ]);

        $subtask = Task::create($data + [
            'parent_id' => $task->id,
            'status' => 'to_do',
            'priority' => $data['priority'] ?? $task->priority,
            'assignee_id' => $data['assignee_id'] ?? null,
            'category_id' => $task->category_id,
            'project_id' => $task->project_id,
            'start_date' => now(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', "Subtask {$subtask->reference} added.");
    }

    /**
     * Re-parent a task, or promote it to top level with a null parent.
     */
    public function move(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:tasks,id'],
        ]);

        try {
            $task->parent_id = $data['parent_id'] ?: null;
            $task->save();
        } catch (\RuntimeException $e) {
            // Thrown by the cycle guard on the model.
            return back()->withErrors(['parent_id' => $e->getMessage()]);
        }

        return back()->with('status', $task->parent_id
            ? 'Task moved under its new parent.'
            : 'Task promoted to top level.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:'.implode(',', Task::STATUSES)],
            'priority' => ['nullable', 'in:low,medium,high'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'category_id' => ['nullable', 'exists:task_categories,id'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'is_continuous' => ['nullable', 'boolean'],
        ]);

        $task = Task::create($data + [
            'status' => $data['status'] ?? 'to_do',
            'priority' => $data['priority'] ?? 'medium',
            'is_continuous' => $request->boolean('is_continuous'),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('tasks.board')
            ->with('status', "Task {$task->reference} created.");
    }

    public function updateStatus(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', Task::STATUSES)],
        ]);

        $task->update(['status' => $data['status']]);

        return response()->json([
            'ok' => true,
            'id' => $task->id,
            'status' => $task->status,
            'label' => $task->statusLabel(),
        ]);
    }

    public function logTime(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $data = $request->validate([
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'entry_date' => ['nullable', 'date'],
        ]);

        TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'minutes' => $data['minutes'],
            'entry_date' => $data['entry_date'] ?? now()->toDateString(),
        ]);

        return back()->with('status', 'Time logged.');
    }

    private function authorizeTask(Task $task): void
    {
        $user = auth()->user();
        abort_unless(
            $user->isManager() || $task->assignee_id === $user->id || $task->created_by === $user->id,
            403,
            'You can only view your own tasks.'
        );
    }
}
