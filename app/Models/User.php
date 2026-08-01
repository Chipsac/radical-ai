<?php

namespace App\Models;

use App\Support\Modules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Implements MustVerifyEmail: a signup must prove it owns the address before
 * the workspace opens. Invited users are verified on acceptance instead —
 * clicking a link in the invitation email already proves it.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'organization_id',
        'role',
        'modules',
        'avatar_initials',
        'last_login_at',
        'last_login_ip',
        'ui_state',
        // Invited users have already proven ownership of their address by
        // opening the invitation email, so acceptance sets this directly.
        // Without it here, mass assignment drops it and they get stuck at
        // the verification wall.
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'ui_state' => 'array',
            'modules' => 'array',
        ];
    }

    // ---- Module access --------------------------------------------------

    /**
     * Modules this user may open.
     *
     * Admins always get everything — an admin who could lock themselves out
     * of Settings would be a support incident waiting to happen. A null value
     * means nobody has decided yet, so we fall back to full access rather
     * than presenting a new joiner with an empty product.
     */
    public function allowedModules(): array
    {
        if ($this->isAdmin()) {
            return Modules::ALL;
        }

        if ($this->modules === null) {
            return Modules::ALL;
        }

        return Modules::sanitise($this->modules);
    }

    public function hasModule(string $module): bool
    {
        return in_array($module, $this->allowedModules(), true);
    }

    public function hasAllModules(): bool
    {
        return count($this->allowedModules()) === count(Modules::ALL);
    }

    /**
     * Replace this user's module grants. Admins are left untouched because
     * their access is implicit and always complete.
     */
    public function setModules(array $modules): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $this->forceFill(['modules' => Modules::sanitise($modules)])->save();
    }

    /** The first module this user can actually open, for post-login landing. */
    public function defaultModuleRoute(): ?string
    {
        $first = $this->allowedModules()[0] ?? null;

        return $first ? (Modules::catalogue()[$first]['route'] ?? null) : null;
    }

    // ---- Two-factor ----------------------------------------------------

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function recoveryCodes(): array
    {
        if (! $this->two_factor_recovery_codes) {
            return [];
        }

        return json_decode(decrypt($this->two_factor_recovery_codes), true) ?: [];
    }

    /**
     * Consume a one-time recovery code. Returns false if it wasn't valid.
     */
    public function useRecoveryCode(string $code): bool
    {
        $codes = $this->recoveryCodes();
        $index = array_search(trim($code), $codes, true);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $this->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(array_values($codes))),
        ])->save();

        return true;
    }

    // ---- UI state (dismissed tips / completed tours) -------------------

    public function hasSeen(string $key): bool
    {
        return in_array($key, $this->ui_state['seen'] ?? [], true);
    }

    public function markSeen(string $key): void
    {
        $state = $this->ui_state ?? [];
        $seen = $state['seen'] ?? [];

        if (! in_array($key, $seen, true)) {
            $seen[] = $key;
            $state['seen'] = $seen;
            $this->forceFill(['ui_state' => $state])->save();
        }
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class, 'owner_id');
    }

    public function initials(): string
    {
        if ($this->avatar_initials) {
            return $this->avatar_initials;
        }

        return collect(explode(' ', $this->name))
            ->map(fn ($p) => mb_substr($p, 0, 1))
            ->take(2)
            ->implode('');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }
}
