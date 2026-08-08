<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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
        'assistant_enabled_at' => 'datetime',
        'assistant_expires_at' => 'datetime',
    ];

    /**
     * Is the paid assistant add-on active for this organisation?
     *
     * Commercial question only — it says nothing about whether a given person
     * may use it. That is the per-user module grant, checked separately. Both
     * must pass; neither implies the other.
     */
    public function hasAssistant(): bool
    {
        if ($this->assistant_enabled_at === null) {
            return false;
        }

        // A null expiry is an open-ended subscription, not an expired one.
        return $this->assistant_expires_at === null
            || $this->assistant_expires_at->isFuture();
    }

    /**
     * Tokens consumed by the assistant in the current calendar month.
     *
     * Read from the message log rather than a running counter on the row: a
     * counter drifts the first time a write fails halfway, and this number
     * decides whether a customer gets cut off.
     */
    public function assistantTokensThisMonth(): int
    {
        return (int) AssistantMessage::withoutGlobalScopes()
            ->where('organization_id', $this->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum(DB::raw('input_tokens + output_tokens'));
    }

    public function assistantCapReached(): bool
    {
        return $this->assistantTokensThisMonth() >= $this->assistant_monthly_token_cap;
    }

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
