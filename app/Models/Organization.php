<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function nextTaskSeq(): int
    {
        $this->increment('task_seq');

        return $this->task_seq;
    }
}
