<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToOrganization
{
    protected static function bootBelongsToOrganization(): void
    {
        static::creating(function ($model) {
            if (! $model->organization_id && auth()->check()) {
                $model->organization_id = auth()->user()->organization_id;
            }
        });

        static::addGlobalScope('organization', function (Builder $builder) {
            if (auth()->check() && auth()->user()->organization_id) {
                $builder->where(
                    $builder->getModel()->getTable().'.organization_id',
                    auth()->user()->organization_id
                );
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
