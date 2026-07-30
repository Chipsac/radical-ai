<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToOrganization
{
    protected static function bootBelongsToOrganization(): void
    {
        // Stamp the tenant on create, and refuse to write a row belonging to
        // a different tenant than the active one.
        static::creating(function (Model $model) {
            $tenant = app(TenantContext::class);

            if (! $model->organization_id && $tenant->hasTenant()) {
                $model->organization_id = $tenant->id();
            }

            static::guardTenant($model);
        });

        static::updating(function (Model $model) {
            static::guardTenant($model);
        });

        static::addGlobalScope('organization', function (Builder $builder) {
            $tenant = app(TenantContext::class);

            if ($tenant->isDisabled()) {
                return;
            }

            $table = $builder->getModel()->getTable();

            // Fail closed: no tenant context means no rows, never all rows.
            $builder->where($table.'.organization_id', $tenant->id());
        });
    }

    /**
     * Block cross-tenant writes even when an id is passed explicitly.
     */
    protected static function guardTenant(Model $model): void
    {
        $tenant = app(TenantContext::class);

        if ($tenant->isDisabled() || ! $tenant->hasTenant()) {
            return;
        }

        if ($model->organization_id !== null && (int) $model->organization_id !== (int) $tenant->id()) {
            throw new \RuntimeException(sprintf(
                'Cross-tenant write blocked on %s: active tenant is %s, row belongs to %s.',
                $model::class,
                $tenant->id(),
                $model->organization_id
            ));
        }
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Escape hatch for genuinely cross-tenant queries.
     */
    public function scopeAcrossTenants(Builder $query): Builder
    {
        return $query->withoutGlobalScope('organization');
    }
}
