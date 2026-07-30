<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'organization_id',
        'role',
        'avatar_initials',
        'job_title',
        'last_login_at',
        'last_login_ip',
        'ui_state',
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
        ];
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
