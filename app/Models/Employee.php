<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use BelongsToOrganization;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function isOnLeave(?\DateTimeInterface $date = null): bool
    {
        $date = $date ? $date->format('Y-m-d') : now()->toDateString();

        return $this->leaveRequests()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    public function utilisationMinutes(int $days = 30): int
    {
        return (int) TimeEntry::where('user_id', $this->user_id)
            ->where('entry_date', '>=', now()->subDays($days)->toDateString())
            ->sum('minutes');
    }
}
