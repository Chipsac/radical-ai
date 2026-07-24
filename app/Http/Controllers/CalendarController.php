<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
            : now()->startOfMonth();

        $approved = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $month->copy()->endOfMonth())
            ->whereDate('end_date', '>=', $month)
            ->with(['employee.user', 'leaveType'])
            ->get();

        // Map each day of the month to the people on leave that day
        $days = [];
        for ($d = $month->copy(); $d->month === $month->month; $d->addDay()) {
            $days[$d->toDateString()] = $approved->filter(
                fn ($lr) => $d->betweenIncluded($lr->start_date, $lr->end_date)
            )->values();
        }

        return view('hr.calendar', [
            'month' => $month,
            'days' => $days,
            'prev' => $month->copy()->subMonth()->format('Y-m'),
            'next' => $month->copy()->addMonth()->format('Y-m'),
        ]);
    }
}
