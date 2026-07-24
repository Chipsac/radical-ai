<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use BelongsToOrganization;

    protected $guarded = [];

    protected $casts = [
        'due_at' => 'datetime',
        'done' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
