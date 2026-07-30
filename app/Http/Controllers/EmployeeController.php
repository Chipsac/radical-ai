<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['user', 'manager'])
            ->get()
            ->sortBy(fn ($e) => $e->user->name)
            ->values();

        return view('hr.index', [
            'employees' => $employees,
            'byDepartment' => $employees->groupBy('department'),
        ]);
    }

    public function show(Request $request, Employee $employee)
    {
        $user = $request->user();
        abort_unless(
            $user->isManager() || $user->employee?->id === $employee->id,
            403,
            'You are only authorized to view your own employee details.'
        );

        $employee->load(['user', 'manager', 'leaveBalances.leaveType', 'leaveRequests.leaveType']);

        return view('hr.show', [
            'employee' => $employee,
            'utilisationHours' => round($employee->utilisationMinutes(30) / 60, 1),
            'openTasks' => $employee->user
                ? $employee->user->tasks()->where('status', '!=', 'done')->with('category')->get()
                : collect(),
        ]);
    }
}
