<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\TaskCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Organization, 1: User} */
    private function freshTenant(string $step = 'personal'): array
    {
        $org = Organization::create([
            'name' => 'New Co', 'slug' => 'new-co', 'task_seq' => 0,
            'onboarding_step' => $step,
        ]);

        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        Employee::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'job_title' => 'Founder',
            'status' => 'active',
        ]);

        return [$org, $user];
    }

    private function organisationPayload(array $overrides = []): array
    {
        return array_merge([
            'organization_name' => 'New Co Ltd',
            'industry' => 'Software & technology',
            'team_size' => '6-25',
            'plan_tier' => 'starter',
            'starting_point' => 'blank',
        ], $overrides);
    }

    public function test_users_are_funnelled_into_onboarding_until_it_is_done(): void
    {
        [, $user] = $this->freshTenant();

        $this->actingAs($user)->get('/tasks/board')->assertRedirect(route('onboarding.show'));
    }

    public function test_onboarding_itself_is_reachable_during_setup(): void
    {
        [, $user] = $this->freshTenant();

        $this->actingAs($user)->get(route('onboarding.show'))->assertOk();
    }

    // ---- Step 3: personal information ---------------------------------

    public function test_personal_step_saves_details_and_advances(): void
    {
        [$org, $user] = $this->freshTenant();

        $this->actingAs($user)->post(route('onboarding.personal'), [
            'name' => 'Jamie Reeves',
            'job_title' => 'Managing Director',
            'phone' => '+353 91 123456',
        ])->assertRedirect(route('onboarding.show'));

        $user->refresh();
        $this->assertSame('Jamie Reeves', $user->name);
        $this->assertSame('Managing Director', $user->employee->job_title);
        $this->assertSame('+353 91 123456', $user->employee->phone);
        $this->assertSame('organisation', $org->fresh()->onboarding_step);
    }

    public function test_personal_step_requires_a_name(): void
    {
        [, $user] = $this->freshTenant();

        $this->actingAs($user)->post(route('onboarding.personal'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    // ---- Step 4: organisation setup ------------------------------------

    public function test_organisation_step_saves_name_plan_and_advances(): void
    {
        [$org, $user] = $this->freshTenant('organisation');

        $this->actingAs($user)->post(route('onboarding.organisation'), $this->organisationPayload())
            ->assertRedirect(route('onboarding.show'));

        $org->refresh();
        $this->assertSame('New Co Ltd', $org->name);
        $this->assertSame('starter', $org->plan_tier);
        $this->assertSame('Software & technology', $org->industry);
        $this->assertSame('team', $org->onboarding_step);
    }

    public function test_organisation_step_replaces_leave_types_and_categories(): void
    {
        [$org, $user] = $this->freshTenant('organisation');

        LeaveType::create(['organization_id' => $org->id, 'name' => 'Old Leave', 'paid' => true, 'default_allowance_days' => 5]);
        TaskCategory::create(['organization_id' => $org->id, 'name' => 'Old Category', 'color' => '#000000']);

        $this->actingAs($user)->post(route('onboarding.organisation'), $this->organisationPayload([
            'leave_types' => [
                ['name' => 'Holiday', 'days' => 30],
                ['name' => 'Unpaid Leave', 'days' => 0],
                ['name' => '', 'days' => null], // blank rows are ignored
            ],
            'categories' => [
                ['name' => 'Delivery', 'color' => '#22C55E'],
                ['name' => '', 'color' => '#E0A44E'],
            ],
        ]));

        $this->actingAs($user);

        $this->assertSame(['Holiday', 'Unpaid Leave'], LeaveType::pluck('name')->all());
        $this->assertSame(['Delivery'], TaskCategory::pluck('name')->all());

        // "Unpaid" is inferred as unpaid leave.
        $this->assertFalse(LeaveType::where('name', 'Unpaid Leave')->first()->paid);
    }

    public function test_sample_data_populates_the_workspace(): void
    {
        [, $user] = $this->freshTenant('organisation');

        $this->actingAs($user)->post(route('onboarding.organisation'),
            $this->organisationPayload(['starting_point' => 'sample']));

        $this->actingAs($user);
        $this->assertGreaterThan(0, Deal::count(), 'Sample data should create deals.');
    }

    public function test_a_blank_start_creates_no_sample_records(): void
    {
        [, $user] = $this->freshTenant('organisation');

        $this->actingAs($user)->post(route('onboarding.organisation'),
            $this->organisationPayload(['starting_point' => 'blank']));

        $this->actingAs($user);
        $this->assertSame(0, Deal::count());
    }

    // ---- Step 5/6: team -------------------------------------------------

    public function test_team_step_completes_onboarding(): void
    {
        [$org, $user] = $this->freshTenant('team');

        $this->actingAs($user)->post(route('onboarding.team'))
            ->assertRedirect(route('home'));

        $this->assertNotNull($org->fresh()->onboarding_completed_at);
    }

    public function test_onboarding_can_be_skipped(): void
    {
        [$org, $user] = $this->freshTenant();

        $this->actingAs($user)->get(route('onboarding.skip'))->assertRedirect(route('home'));

        $this->assertNotNull($org->fresh()->onboarding_completed_at);
    }

    public function test_completed_onboarding_is_not_shown_again(): void
    {
        [$org, $user] = $this->freshTenant();
        $org->update(['onboarding_completed_at' => now()]);

        $this->actingAs($user)->get(route('onboarding.show'))->assertRedirect(route('home'));
    }
}
