<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Team extends Model
{
    use BelongsToOrganization;

    protected $guarded = [];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function lead()
    {
        return $this->belongsTo(User::class, 'lead_id');
    }

    // ---- Coverage --------------------------------------------------------

    /** Employees who are on approved leave on a given day. */
    public function absentOn(CarbonInterface $date): Collection
    {
        $day = $date->toDateString();

        return LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>=', $day)
            ->whereIn('employee_id', $this->employees()->pluck('id'))
            ->with('employee.user')
            ->get();
    }

    public function activeHeadcount(): int
    {
        return $this->employees()->where('status', 'active')->count();
    }

    /**
     * How many people are at work on a given day.
     *
     * Weekends are treated as covered — nobody is expected in, so flagging
     * them would bury the real warnings in noise.
     */
    public function coverageOn(CarbonInterface $date): array
    {
        if ($date->isWeekend()) {
            return ['weekend' => true, 'present' => 0, 'required' => 0, 'covered' => true];
        }

        $headcount = $this->activeHeadcount();
        $absent = $this->absentOn($date)->count();
        $present = max($headcount - $absent, 0);

        return [
            'weekend' => false,
            'present' => $present,
            'required' => $this->minimum_cover,
            'covered' => $this->minimum_cover === 0 || $present >= $this->minimum_cover,
        ];
    }

    /**
     * Which working days in a range would fall below minimum cover if the
     * given leave request were approved.
     *
     * Returns an empty collection when the team is fine — so an empty result
     * means "no warning", not "no data".
     */
    public function daysLeftUncoveredBy(LeaveRequest $request): Collection
    {
        if ($this->minimum_cover === 0 || $this->activeHeadcount() === 0) {
            return collect();
        }

        $uncovered = collect();

        for ($day = $request->start_date->copy(); $day->lte($request->end_date); $day->addDay()) {
            if ($day->isWeekend()) {
                continue;
            }

            $coverage = $this->coverageOn($day);

            // The requester is not yet counted as absent, so approving this
            // takes one more person out on each of these days.
            $wouldBePresent = max($coverage['present'] - 1, 0);

            if ($wouldBePresent < $this->minimum_cover) {
                $uncovered->push([
                    'date' => $day->copy(),
                    'present_after' => $wouldBePresent,
                    'required' => $this->minimum_cover,
                ]);
            }
        }

        return $uncovered;
    }
}
