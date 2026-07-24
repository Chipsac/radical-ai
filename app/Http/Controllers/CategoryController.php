<?php

namespace App\Http\Controllers;

use App\Models\TaskCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return view('tasks.categories', [
            'categories' => TaskCategory::withCount('tasks')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'prefix' => ['nullable', 'string', 'max:8'],
        ]);

        TaskCategory::create($data);

        return back()->with('status', 'Category created.');
    }
}
