<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\TaskCategory;
use App\Models\User;
use App\Support\Modules;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Creates a brand-new tenant: the organization, its owning admin user, and
 * the minimum reference data the app needs to be usable on first login.
 *
 * Everything happens in one transaction — a half-provisioned tenant is worse
 * than a failed signup.
 */
class TenantProvisioner
{
    public function __construct(private TenantContext $tenant) {}

    public function provision(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $org = $this->tenant->withoutTenancy(fn () => Organization::create([
                'name' => $data['organization_name'],
                'slug' => $this->uniqueSlug($data['organization_name']),
                'plan_tier' => 'trial',
                'onboarding_step' => 'personal',
                'trial_ends_at' => now()->addDays(Organization::TRIAL_DAYS),
            ]));

            $user = $this->tenant->asTenant($org->id, function () use ($org, $data) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'organization_id' => $org->id,
                    'role' => 'admin',
                ]);

                $this->ensureRolesExist();
                $user->assignRole('admin');

                Employee::create([
                    'organization_id' => $org->id,
                    'user_id' => $user->id,
                    'department' => 'Leadership',
                    'job_title' => $data['job_title'] ?? 'Founder',
                    'start_date' => now(),
                    'status' => 'active',
                ]);

                // The founder is an admin, so module access is implicit and
                // complete — recorded explicitly for clarity in the UI.
                $user->forceFill(['modules' => Modules::ALL])->save();

                $this->seedReferenceData($org);

                return $user;
            });

            $org->update(['owner_id' => $user->id]);

            AuditLog::record('organization.created', $user, $org, [
                'organization' => $org->name,
            ]);

            return $user;
        });
    }

    /**
     * Reference data every tenant needs: leave types and task categories.
     * Deliberately minimal — the onboarding wizard offers richer sample data.
     */
    private function seedReferenceData(Organization $org): void
    {
        foreach ([
            ['Annual Leave', true, 25],
            ['Sick Leave', true, 10],
            ['Unpaid Leave', false, 0],
        ] as [$name, $paid, $days]) {
            LeaveType::create([
                'organization_id' => $org->id,
                'name' => $name,
                'paid' => $paid,
                'default_allowance_days' => $days,
            ]);
        }

        foreach ([
            ['General', '#8A8A8A', 'GEN'],
            ['Sales', '#E0A44E', 'SAL'],
            ['Delivery', '#22C55E', 'DEL'],
            ['Operations', '#3B82F6', 'OPS'],
        ] as [$name, $color, $prefix]) {
            TaskCategory::create([
                'organization_id' => $org->id,
                'name' => $name,
                'color' => $color,
                'prefix' => $prefix,
            ]);
        }
    }

    private function ensureRolesExist(): void
    {
        foreach (['admin', 'manager', 'employee'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'org';
        $slug = $base;
        $i = 1;

        while (Organization::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
