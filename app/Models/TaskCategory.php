<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class TaskCategory extends Model
{
    use BelongsToOrganization;

    protected $guarded = [];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'category_id');
    }
}
