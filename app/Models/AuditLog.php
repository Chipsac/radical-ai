<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Write an audit entry. Deliberately not tenant-scoped via the trait so
     * that signup (which has no tenant context yet) can still be recorded.
     */
    public static function record(
        string $action,
        ?User $user = null,
        ?Model $subject = null,
        array $meta = []
    ): self {
        $user ??= auth()->user();

        return static::create([
            'organization_id' => $subject instanceof Organization
                ? $subject->id
                : ($user?->organization_id),
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
            'meta' => $meta ?: null,
        ]);
    }
}
