<?php

namespace App\Http\Controllers;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        $pendingApprovals = $user->isManager()
            ? LeaveRequest::where('status', 'pending')
                ->with(['employee.user', 'employee.team', 'leaveType'])
                ->oldest('start_date')->get()
            : collect();

        // Warn the manager before they approve, rather than after: which days
        // would drop the requester's team below its minimum cover.
        $coverageWarnings = $pendingApprovals->mapWithKeys(function (LeaveRequest $req) {
            $team = $req->employee?->team;

            return [$req->id => $team ? $team->daysLeftUncoveredBy($req) : collect()];
        });

        return view('hr.leave', [
            'employee' => $employee,
            'leaveTypes' => LeaveType::orderBy('name')->get(),
            'balances' => $employee
                ? LeaveBalance::where('employee_id', $employee->id)
                    ->where('year', now()->year)->with('leaveType')->get()
                : collect(),
            'myRequests' => $employee
                ? LeaveRequest::where('employee_id', $employee->id)->with('leaveType')->latest()->get()
                : collect(),
            'pendingApprovals' => $pendingApprovals,
            'coverageWarnings' => $coverageWarnings,
            'teamLeave' => $this->teamLeave($request),
            'team' => $employee?->team,
        ]);
    }

    /**
     * Approved leave for the requester's own team.
     *
     * This is the point of teams: you can see when your colleagues are off
     * without asking a manager to look it up for you.
     */
    private function teamLeave(Request $request)
    {
        $employee = $request->user()->employee;

        if (! $employee?->team_id) {
            return collect();
        }

        return LeaveRequest::where('status', 'approved')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->whereIn('employee_id', $employee->team->employees()->pluck('id'))
            ->with(['employee.user', 'leaveType'])
            ->orderBy('start_date')
            ->get();
    }

    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'No employee record for this user.');

        $data = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $start = \Carbon\Carbon::parse($data['start_date']);
        $end = \Carbon\Carbon::parse($data['end_date']);

        // Count weekdays only
        $days = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (! $d->isWeekend()) {
                $days++;
            }
        }

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days' => max($days, 0.5),
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Leave request submitted for approval.');
    }

    public function decide(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
        ]);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('status', 'Request already decided.');
        }

        $leaveRequest->update([
            'status' => $data['decision'],
            'approver_id' => $request->user()->id,
            'decided_at' => now(),
        ]);

        if ($data['decision'] === 'approved') {
            // Fall back to the leave type's own allowance rather than a magic
            // number, so unpaid leave (0 days) doesn't silently gain an entitlement.
            $balance = LeaveBalance::firstOrCreate(
                [
                    'employee_id' => $leaveRequest->employee_id,
                    'leave_type_id' => $leaveRequest->leave_type_id,
                    'year' => now()->year,
                ],
                [
                    'entitlement_days' => $leaveRequest->leaveType?->default_allowance_days ?? 0,
                    'taken_days' => 0,
                ]
            );

            $balance->increment('taken_days', $leaveRequest->days);
        }

        $message = 'Leave request '.$data['decision'].'.';

        // Coverage is advisory: the manager has already seen the warning and
        // decided. Say plainly what the approval did rather than hiding it.
        if ($data['decision'] === 'approved') {
            $team = $leaveRequest->employee?->team;
            $uncovered = $team ? $team->daysLeftUncoveredBy($leaveRequest) : collect();

            if ($uncovered->isNotEmpty()) {
                $message .= sprintf(
                    ' Note: %s is now below minimum cover on %d working day%s.',
                    $team->name,
                    $uncovered->count(),
                    $uncovered->count() === 1 ? '' : 's'
                );
            }
        }

        return back()->with('status', $message);
    }
}
