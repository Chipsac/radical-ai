<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'manager', 'employee'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->org = Organization::create([
            'name' => 'Access Co', 'slug' => 'access-co', 'task_seq' => 0,
            'onboarding_completed_at' => now(),
        ]);
    }

    private function member(string $role = 'employee', ?array $modules = null): User
    {
        $user = User::factory()->create([
            'organization_id' => $this->org->id,
            'role' => $role,
            'modules' => $modules,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_a_user_granted_only_crm_can_reach_crm(): void
    {
        $user = $this->member('employee', [Modules::CRM]);

        $this->actingAs($user)->get('/crm/deals')->assertOk();
    }

    public function test_a_user_granted_only_crm_is_blocked_from_tasks_and_hr(): void
    {
        $user = $this->member('employee', [Modules::CRM]);

        $this->actingAs($user)->get('/tasks/board')->assertForbidden();
        $this->actingAs($user)->get('/hr')->assertForbidden();
        $this->actingAs($user)->get('/reports')->assertForbidden();
    }

    public function test_a_user_granted_only_hr_is_blocked_from_crm_and_tasks(): void
    {
        $user = $this->member('employee', [Modules::HR]);

        $this->actingAs($user)->get('/hr')->assertOk();
        $this->actingAs($user)->get('/crm/deals')->assertForbidden();
        $this->actingAs($user)->get('/tasks/board')->assertForbidden();
    }

    public function test_a_user_with_no_modules_is_blocked_everywhere_but_home(): void
    {
        $user = $this->member('employee', []);

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/crm/deals')->assertForbidden();
        $this->actingAs($user)->get('/tasks/board')->assertForbidden();
        $this->actingAs($user)->get('/hr')->assertForbidden();
    }

    public function test_admins_always_have_every_module_even_if_stored_empty(): void
    {
        $admin = $this->member('admin', []);

        $this->assertTrue($admin->hasModule(Modules::CRM));
        $this->actingAs($admin)->get('/crm/deals')->assertOk();
        $this->actingAs($admin)->get('/tasks/board')->assertOk();
        $this->actingAs($admin)->get('/hr')->assertOk();
    }

    public function test_null_modules_means_full_access_so_nobody_is_locked_out(): void
    {
        $user = $this->member('employee', null);

        $this->assertTrue($user->hasAllModules());
        $this->actingAs($user)->get('/tasks/board')->assertOk();
    }

    public function test_navigation_hides_modules_the_user_cannot_open(): void
    {
        $user = $this->member('employee', [Modules::CRM]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Deal Pipeline');
        $response->assertDontSee('Task Dashboard');
        $response->assertDontSee('Payslips');
    }

    public function test_a_manager_can_change_someone_elses_modules(): void
    {
        $manager = $this->member('manager');
        $member = $this->member('employee', Modules::ALL);

        $this->actingAs($manager)
            ->patch(route('team.modules', $member), ['modules' => [Modules::HR]])
            ->assertSessionHasNoErrors();

        $member->refresh();
        $this->assertSame([Modules::HR], $member->allowedModules());
    }

    public function test_revoking_every_module_is_allowed_and_takes_effect(): void
    {
        $manager = $this->member('manager');
        $member = $this->member('employee', Modules::ALL);

        $this->actingAs($manager)->patch(route('team.modules', $member), ['modules' => []]);

        $this->actingAs($member->fresh())->get('/crm/deals')->assertForbidden();
    }

    public function test_admin_module_access_cannot_be_restricted(): void
    {
        $manager = $this->member('manager');
        $admin = $this->member('admin', Modules::ALL);

        $this->actingAs($manager)
            ->patch(route('team.modules', $admin), ['modules' => []])
            ->assertSessionHasErrors('modules');

        $this->assertTrue($admin->fresh()->hasModule(Modules::CRM));
    }

    public function test_module_changes_cannot_cross_organisations(): void
    {
        $otherOrg = Organization::create([
            'name' => 'Other', 'slug' => 'other-co', 'task_seq' => 0,
            'onboarding_completed_at' => now(),
        ]);
        $outsider = User::factory()->create(['organization_id' => $otherOrg->id, 'role' => 'employee']);

        $manager = $this->member('manager');

        $this->actingAs($manager)
            ->patch(route('team.modules', $outsider), ['modules' => []])
            ->assertForbidden();
    }

    public function test_the_last_admin_cannot_be_demoted(): void
    {
        $admin = $this->member('admin');

        $this->actingAs($admin)
            ->patch(route('team.role', $admin), ['role' => 'employee'])
            ->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_module_changes_are_audit_logged(): void
    {
        $manager = $this->member('manager');
        $member = $this->member('employee', Modules::ALL);

        $this->actingAs($manager)->patch(route('team.modules', $member), ['modules' => [Modules::CRM]]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'team.modules_changed',
            'user_id' => $manager->id,
        ]);
    }
}
