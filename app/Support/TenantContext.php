<?php

namespace App\Support;

/**
 * Holds the active tenant for the current request/job.
 *
 * Resolution order:
 *   1. An explicitly set tenant id (queued jobs, console commands, tests)
 *   2. The authenticated user's organization
 *
 * Scoping deliberately fails closed: when nothing identifies a tenant, the
 * global scope matches no rows rather than silently returning every tenant's
 * data. Genuinely cross-tenant work must opt out via `withoutTenancy()`.
 */
class TenantContext
{
    private ?int $tenantId = null;

    private bool $explicitlySet = false;

    private bool $disabled = false;

    public function set(?int $tenantId): void
    {
        $this->tenantId = $tenantId;
        $this->explicitlySet = true;
    }

    public function forget(): void
    {
        $this->tenantId = null;
        $this->explicitlySet = false;
    }

    public function id(): ?int
    {
        if ($this->explicitlySet) {
            return $this->tenantId;
        }

        return auth()->check() ? auth()->user()->organization_id : null;
    }

    public function hasTenant(): bool
    {
        return $this->id() !== null;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Run a callback with tenant scoping switched off — for admin tooling,
     * migrations and seeders that legitimately span tenants.
     */
    public function withoutTenancy(callable $callback): mixed
    {
        $previous = $this->disabled;
        $this->disabled = true;

        try {
            return $callback();
        } finally {
            $this->disabled = $previous;
        }
    }

    /**
     * Run a callback as a specific tenant, restoring the previous one after.
     */
    public function asTenant(?int $tenantId, callable $callback): mixed
    {
        $prevId = $this->tenantId;
        $prevSet = $this->explicitlySet;

        $this->set($tenantId);

        try {
            return $callback();
        } finally {
            $this->tenantId = $prevId;
            $this->explicitlySet = $prevSet;
        }
    }
}
