<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use BelongsToOrganization;

    protected $guarded = [];

    protected $casts = [
        'pay_date' => 'date',
        'gross' => 'decimal:2',
        'net' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
