<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskProgressUpdate;
use Illuminate\Http\Request;

class TaskProgressController extends Controller
{
    public function store(Request $request, Task $task)
    {
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
