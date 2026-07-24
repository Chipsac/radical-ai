<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use BelongsToOrganization;

    protected $guarded = [];

    public function lead()
    {
        return $this->belongsTo(User::class, 'lead_id');
    }
}
