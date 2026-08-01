<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Models\Task;
use App\Support\Modules;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * The dashboard only assembles data for modules the user may open — both
     * so nothing leaks into the view, and so we don't run queries whose
     * results would be thrown away.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        $canCrm = $user->hasModule(Modules::CRM);
        $canTasks = $user->hasModule(Modules::TASKS);
        $canHr = $user->hasModule(Modules::HR);

        $data = [
            'canCrm' => $canCrm,
            'canTasks' => $canTasks,
            'canHr' => $canHr,
            'allowedModules' => $user->allowedModules(),

            // Defaults so the view never has to guard for missing keys.
            'openDealCount' => 0,
            'openDealValue' => 0,
            'tasksDueToday' => 0,
            'onLeaveToday' => collect(),
            'myTasks' => collect(),
            'pipeline' => collect(),
            'pipelineCounts' => collect(),
            'latestPayslip' => null,
            'pendingLeave' => collect(),
            'nextPayday' => now()->day <= 28 ? now()->setDay(28) : now()->addMonthNoOverflow()->setDay(28),
        ];

        if ($canCrm) {
            $openDeals = Deal::whereNotIn('stage', ['won', 'lost']);

            $data['openDealCount'] = (clone $openDeals)->count();
            $data['openDealValue'] = (clone $openDeals)->sum('value');
            $data['pipeline'] = Deal::selectRaw('stage, sum(value) as total')
                ->groupBy('stage')->pluck('total', 'stage');
            $data['pipelineCounts'] = Deal::selectRaw('stage, count(*) as n')
                ->groupBy('stage')->pluck('n', 'stage');
        }

        if ($canTasks) {
            $data['tasksDueToday'] = Task::whereDate('due_date', $today)
                ->where('status', '!=', 'done')->count();

            $data['myTasks'] = Task::where('assignee_id', $user->id)
                ->where('status', '!=', 'done')
                ->orderByRaw('due_date is null, due_date')
                ->with('category')
                ->take(6)
                ->get();
        }

        if ($canHr) {
            $data['onLeaveToday'] = LeaveRequest::where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->with(['employee.user', 'leaveType'])
                ->get();

            if ($user->employee) {
                $data['latestPayslip'] = Payslip::where('employee_id', $user->employee->id)
                    ->orderByDesc('pay_date')
                    ->first();
            }

            if ($user->isManager()) {
                $data['pendingLeave'] = LeaveRequest::where('status', 'pending')
                    ->with(['employee.user', 'leaveType'])
                    ->get();
            }
        }

        return view('home', $data);
    }
}
