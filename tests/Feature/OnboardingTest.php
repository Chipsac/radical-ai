<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function freshTenant(): array
    {
        $org = Organization::create([
            'name' => 'New Co', 'slug' => 'new-co', 'task_seq' => 0,
            'onboarding_step' => 'profile',
        ]);

        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'admin']);

        return [$org, $user];
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

    public function test_wizard_advances_through_each_step(): void
    {
        [$org, $user] = $this->freshTenant();

        $this->actingAs($user)->post(route('onboarding.profile'), [
            'industry' => 'Software & technology',
            'team_size' => '6-25',
        ])->assertRedirect(route('onboarding.show'));

        $this->assertSame('team', $org->fresh()->onboarding_step);

        $this->actingAs($user)->post(route('onboarding.team'));
        $this->assertSame('data', $org->fresh()->onboarding_step);
    }

    public function test_choosing_sample_data_populates_the_workspace(): void
    {
        [$org, $user] = $this->freshTenant();
        $org->update(['onboarding_step' => 'data']);

        $this->actingAs($user)->post(route('onboarding.data'), ['starting_point' => 'sample'])
            ->assertRedirect(route('home'));

        $org->refresh();
        $this->assertNotNull($org->onboarding_completed_at);

        $this->actingAs($user);
        $this->assertGreaterThan(0, Deal::count(), 'Sample data should create deals.');
    }

    public function test_choosing_a_blank_workspace_creates_no_sample_records(): void
    {
        [$org, $user] = $this->freshTenant();
        $org->update(['onboarding_step' => 'data']);

        $this->actingAs($user)->post(route('onboarding.data'), ['starting_point' => 'blank']);

        $this->actingAs($user);
        $this->assertSame(0, Deal::count());
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
