<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Models\Task;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        $openDeals = Deal::whereNotIn('stage', ['won', 'lost']);
        $myTasks = Task::where('assignee_id', $user->id)
            ->where('status', '!=', 'done')
            ->orderByRaw('due_date is null, due_date')
            ->with('category')
            ->take(6)
            ->get();

        $onLeaveToday = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with(['employee.user', 'leaveType'])
            ->get();

        $pipeline = Deal::selectRaw('stage, count(*) as n, sum(value) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');
        $pipelineCounts = Deal::selectRaw('stage, count(*) as n')
            ->groupBy('stage')
            ->pluck('n', 'stage');

        $latestPayslip = null;
        if ($user->employee) {
            $latestPayslip = Payslip::where('employee_id', $user->employee->id)
                ->orderByDesc('pay_date')
                ->first();
        }

        return view('home', [
            'openDealCount' => (clone $openDeals)->count(),
            'openDealValue' => (clone $openDeals)->sum('value'),
            'tasksDueToday' => Task::whereDate('due_date', $today)->where('status', '!=', 'done')->count(),
            'onLeaveToday' => $onLeaveToday,
            'nextPayday' => now()->day <= 28 ? now()->setDay(28) : now()->addMonthNoOverflow()->setDay(28),
            'myTasks' => $myTasks,
            'pipeline' => $pipeline,
            'pipelineCounts' => $pipelineCounts,
            'latestPayslip' => $latestPayslip,
            'pendingLeave' => $user->isManager()
                ? LeaveRequest::where('status', 'pending')->with(['employee.user', 'leaveType'])->get()
                : collect(),
        ]);
    }
}
