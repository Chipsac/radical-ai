<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use BelongsToOrganization;

    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function remaining(): float
    {
        return (float) $this->entitlement_days - (float) $this->taken_days;
    }
}
