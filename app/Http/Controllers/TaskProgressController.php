<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskProgressUpdate;
use Illuminate\Http\Request;

class TaskProgressController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $user = $request->user();
        abort_unless(
            $user->isManager() || $task->assignee_id === $user->id || $task->created_by === $user->id,
            403,
            'You can only update your own tasks.'
        );

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        TaskProgressUpdate::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back()->with('status', 'Progress update added.');
    }
}
