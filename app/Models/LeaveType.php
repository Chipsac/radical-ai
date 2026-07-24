<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use BelongsToOrganization;

    protected $guarded = [];

    protected $casts = [
        'paid' => 'boolean',
    ];
}
