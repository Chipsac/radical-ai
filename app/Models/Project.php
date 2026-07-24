<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use BelongsToOrganization;

    protected $guarded = [];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function sourceDeal()
    {
        return $this->belongsTo(Deal::class, 'source_deal_id');
    }
}
