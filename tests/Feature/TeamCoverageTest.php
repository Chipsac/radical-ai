<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeamCoverageTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private User $manager;

    private LeaveType $annual;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'manager', 'employee'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->org = Organization::create([
            'name' => 'Cover Co', 'slug' => 'cover-co', 'task_seq' => 0,
            'onboarding_completed_at' => now(),
        ]);

        $this->manager = User::factory()->create([
            'organization_id' => $this->org->id,
            'role' => 'manager',
            'email_verified_at' => now(),
        ]);
        $this->manager->assignRole('manager');

        $this->annual = LeaveType::create([
            'organization_id' => $this->org->id,
            'name' => 'Annual Leave', 'paid' => true, 'default_allowance_days' => 25,
        ]);
    }

    private function team(string $name, int $cover = 1): Team
    {
        return Team::create([
            'organization_id' => $this->org->id,
            'name' => $name, 'slug' => strtolower($name), 'minimum_cover' => $cover,
        ]);
    }

    private function employee(?Team $team = null, ?User $user = null): Employee
    {
        $user ??= User::factory()->create([
            'organization_id' => $this->org->id,
            'role' => 'employee',
            'email_verified_at' => now(),
        ]);

        return Employee::create([
            'organization_id' => $this->org->id,
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'job_title' => 'Engineer',
            'status' => 'active',
        ]);
    }

    /** A Monday-to-Wednesday request, so weekends never confuse the counts. */
    private function leave(Employee $employee, string $status = 'pending'): LeaveRequest
    {
        $monday = Carbon::now()->next(Carbon::MONDAY)->startOfDay();

        return LeaveRequest::create([
            'organization_id' => $this->org->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $this->annual->id,
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDays(2)->toDateString(),
            'days' => 3,
            'status' => $status,
        ]);
    }

    // ---- Teams -----------------------------------------------------------

    public function test_a_manager_can_create_a_team_with_a_cover_requirement(): void
    {
        $this->actingAs($this->manager)
            ->post(route('hr.teams.store'), [
                'name' => 'Delivery',
                'minimum_cover' => 2,
            ])->assertSessionHasNoErrors();

        $this->actingAs($this->manager);
        $team = Team::firstWhere('name', 'Delivery');

        $this->assertNotNull($team);
        $this->assertSame(2, $team->minimum_cover);
    }

    public function test_people_can_be_assigned_to_and_removed_from_a_team(): void
    {
        $this->actingAs($this->manager);
        $team = $this->team('Delivery');
        $employee = $this->employee();

        $this->actingAs($this->manager)
            ->patch(route('hr.teams.assign', $employee), ['team_id' => $team->id]);
        $this->assertSame($team->id, $employee->fresh()->team_id);

        $this->actingAs($this->manager)
            ->patch(route('hr.teams.assign', $employee), ['team_id' => '']);
        $this->assertNull($employee->fresh()->team_id);
    }

    public function test_deleting_a_team_releases_its_members_rather_than_deleting_them(): void
    {
        $this->actingAs($this->manager);
        $team = $this->team('Delivery');
        $employee = $this->employee($team);

        $this->actingAs($this->manager)->delete(route('hr.teams.destroy', $team));

        $this->assertNotNull($employee->fresh(), 'The person must survive their team.');
        $this->assertNull($employee->fresh()->team_id);
    }

    // ---- Coverage --------------------------------------------------------

    public function test_it_warns_when_approving_would_leave_nobody_in(): void
    {
        $this->actingAs($this->manager);

        $team = $this->team('Solo', 1);
        $employee = $this->employee($team);
        $request = $this->leave($employee);

        $uncovered = $team->fresh()->daysLeftUncoveredBy($request);

        // Monday, Tuesday, Wednesday — three working days with nobody left.
        $this->assertCount(3, $uncovered);
        $this->assertSame(0, $uncovered->first()['present_after']);
    }

    public function test_it_stays_quiet_when_the_team_still_has_cover(): void
    {
        $this->actingAs($this->manager);

        $team = $this->team('Pair', 1);
        $employee = $this->employee($team);
        $this->employee($team); // a colleague who stays in

        $this->assertCount(0, $team->fresh()->daysLeftUncoveredBy($this->leave($employee)));
    }

    public function test_a_team_with_no_cover_rule_never_warns(): void
    {
        $this->actingAs($this->manager);

        $team = $this->team('Flexible', 0);
        $employee = $this->employee($team);

        $this->assertCount(0, $team->fresh()->daysLeftUncoveredBy($this->leave($employee)));
    }

    public function test_weekends_are_never_counted_as_uncovered(): void
    {
        $this->actingAs($this->manager);

        $team = $this->team('Solo', 1);
        $employee = $this->employee($team);

        $saturday = Carbon::now()->next(Carbon::SATURDAY)->startOfDay();
        $request = LeaveRequest::create([
            'organization_id' => $this->org->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $this->annual->id,
            'start_date' => $saturday->toDateString(),
            'end_date' => $saturday->copy()->addDay()->toDateString(),
            'days' => 0.5,
            'status' => 'pending',
        ]);

        $this->assertCount(0, $team->fresh()->daysLeftUncoveredBy($request));
    }

    /**
     * The rule is advisory on purpose: sick leave is unplanned, and a
     * one-person team would otherwise never be able to take leave at all.
     */
    public function test_a_manager_can_still_approve_despite_a_warning(): void
    {
        $this->actingAs($this->manager);

        $team = $this->team('Solo', 1);
        $employee = $this->employee($team);
        $request = $this->leave($employee);

        LeaveBalance::create([
            'organization_id' => $this->org->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $this->annual->id,
            'year' => now()->year,
            'entitlement_days' => 25, 'taken_days' => 0,
        ]);

        $this->actingAs($this->manager)
            ->patch(route('hr.leave.decide', $request), ['decision' => 'approved'])
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_the_approval_message_mentions_the_coverage_gap(): void
    {
        $this->actingAs($this->manager);

        $team = $this->team('Solo', 1);
        $employee = $this->employee($team);
        $request = $this->leave($employee);

        $this->actingAs($this->manager)
            ->patch(route('hr.leave.decide', $request), ['decision' => 'approved']);

        $this->assertStringContainsString('below minimum cover', session('status'));
    }

    // ---- Visibility ------------------------------------------------------

    public function test_team_members_see_each_others_leave(): void
    {
        $this->actingAs($this->manager);

        $team = $this->team('Delivery', 1);
        $colleagueUser = User::factory()->create([
            'organization_id' => $this->org->id, 'name' => 'Pat Colleague',
            'role' => 'employee', 'email_verified_at' => now(),
        ]);
        $colleague = $this->employee($team, $colleagueUser);
        $this->leave($colleague, 'approved');

        $meUser = User::factory()->create([
            'organization_id' => $this->org->id, 'role' => 'employee', 'email_verified_at' => now(),
        ]);
        $meUser->assignRole('employee');
        $this->employee($team, $meUser);

        $this->actingAs($meUser)->get('/hr/leave')->assertSee('Pat Colleague');
    }

    public function test_managers_get_a_whole_team_balance_overview(): void
    {
        $this->actingAs($this->manager);

        $team = $this->team('Delivery');
        $user = User::factory()->create([
            'organization_id' => $this->org->id, 'name' => 'Sam Balance',
            'role' => 'employee', 'email_verified_at' => now(),
        ]);
        $employee = $this->employee($team, $user);

        LeaveBalance::create([
            'organization_id' => $this->org->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $this->annual->id,
            'year' => now()->year,
            'entitlement_days' => 25, 'taken_days' => 5,
        ]);

        $response = $this->actingAs($this->manager)->get(route('hr.leave.overview'));

        $response->assertOk();
        $response->assertSee('Sam Balance');
        $response->assertSee('20');  // 25 entitlement less 5 taken
    }

    public function test_the_overview_is_not_open_to_ordinary_employees(): void
    {
        $user = User::factory()->create([
            'organization_id' => $this->org->id, 'role' => 'employee', 'email_verified_at' => now(),
        ]);
        $user->assignRole('employee');

        $this->actingAs($user)->get(route('hr.leave.overview'))->assertForbidden();
    }
}
