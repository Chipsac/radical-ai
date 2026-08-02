<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;

/**
 * Everyone's leave allowance on one page, so a manager or HR admin does not
 * have to open each person's profile in turn to answer "how much has Ella got
 * left?".
 */
class LeaveOverviewController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->query('year', now()->year);

        $employees = Employee::with(['user', 'leaveBalances.leaveType'])
            ->get()
            ->filter(fn ($e) => $e->user !== null)
            ->sortBy(fn ($e) => $e->user->name)
            ->values();

        $leaveTypes = LeaveType::orderBy('id')->get();

        // Balances keyed by employee then leave type, so the table can render
        // a fixed set of columns without a query per cell.
        $balances = LeaveBalance::where('year', $year)
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->keyBy('leave_type_id'));

        $today = now()->toDateString();
        $onLeaveToday = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('employee_id')
            ->flip();

        $upcoming = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '>', $today)
            ->with(['employee.user', 'leaveType'])
            ->orderBy('start_date')
            ->take(10)
            ->get();

        return view('hr.leave-overview', [
            'employees' => $employees,
            'leaveTypes' => $leaveTypes,
            'balances' => $balances,
            'onLeaveToday' => $onLeaveToday,
            'upcoming' => $upcoming,
            'year' => $year,
            'years' => range(now()->year - 2, now()->year + 1),
        ]);
    }
}
