<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'plan_tier',
        'industry',
        'size_range',
        'billing_status',
        'trial_ends_at',
        'task_seq',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function invitations()
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function nextTaskSeq(): int
    {
        $this->increment('task_seq');

        return $this->task_seq;
    }

    public function planLabel(): string
    {
        return match ($this->plan_tier) {
            'free_trial', 'trial' => 'Free Trial',
            'starter' => 'Starter Plan',
            'pro', 'professional' => 'Professional Plan',
            'enterprise' => 'Enterprise Plan',
            default => ucfirst($this->plan_tier ?? 'Startup'),
        };
    }
}
