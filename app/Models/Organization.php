<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use SoftDeletes;

    /**
     * Ordered steps of the post-signup wizard, mirroring the customer journey:
     * personal details → organisation setup → people & access → finished.
     */
    public const ONBOARDING_STEPS = ['personal', 'organisation', 'team', 'done'];

    /**
     * Length of the free trial, in days.
     *
     * Single source of truth: the provisioner, the signup form, the landing
     * page and the onboarding wizard all read this, so the number quoted in a
     * sales conversation cannot drift away from the number the app grants.
     */
    public const TRIAL_DAYS = 30;

    protected $guarded = [];

    protected $casts = [
        'onboarding_completed_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    public function nextTaskSeq(): int
    {
        $this->increment('task_seq');

        return $this->task_seq;
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    public function onboardingProgress(): int
    {
        if ($this->hasCompletedOnboarding()) {
            return 100;
        }

        $index = array_search($this->onboarding_step, self::ONBOARDING_STEPS, true);

        return (int) round((($index ?: 0) / (count(self::ONBOARDING_STEPS) - 1)) * 100);
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function trialDaysLeft(): int
    {
        return $this->isOnTrial() ? (int) ceil(now()->diffInDays($this->trial_ends_at, false)) : 0;
    }
}
