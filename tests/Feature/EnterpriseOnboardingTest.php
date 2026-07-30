<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_enterprise_registration_provisions_organization_and_admin(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Enterprise',
            'email' => 'john@starkindustries.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => 'Stark Industries',
            'company_slug' => 'stark-industries',
            'plan_tier' => 'pro',
            'size_range' => '51-200',
            'industry' => 'Technology',
        ]);

        $response->assertRedirect('/onboarding');
        $this->assertAuthenticated();

        $org = Organization::where('slug', 'stark-industries')->first();
        $this->assertNotNull($org);
        $this->assertEquals('Stark Industries', $org->name);
        $this->assertEquals('pro', $org->plan_tier);

        $user = User::where('email', 'john@starkindustries.test')->first();
        $this->assertNotNull($user);
        $this->assertEquals($org->id, $user->organization_id);
        $this->assertEquals('admin', $user->role);
        $this->assertFalse($user->hasCompletedOnboarding());

        $this->assertDatabaseHas('employees', [
            'organization_id' => $org->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_onboarding_wizard_progresses_and_completes(): void
    {
        $org = Organization::create(['name' => 'Acme Corp', 'slug' => 'acme-corp', 'task_seq' => 0]);
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'admin',
            'onboarding_step' => 1,
        ]);

        $response = $this->actingAs($user)->post('/onboarding', [
            'step' => 1,
            'industry' => 'Finance',
            'size_range' => '11-50',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(2, $user->fresh()->onboarding_step);

        $completeResponse = $this->actingAs($user)->post('/onboarding', [
            'step' => 3,
        ]);

        $completeResponse->assertRedirect('/');
        $this->assertTrue($user->fresh()->hasCompletedOnboarding());
    }

    public function test_invited_team_member_can_accept_invitation(): void
    {
        $org = Organization::create(['name' => 'Acme Corp', 'slug' => 'acme-corp', 'task_seq' => 0]);
        $inviter = User::factory()->create(['organization_id' => $org->id, 'role' => 'admin']);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $org->id,
            'email' => 'engineer@acme-corp.test',
            'role' => 'employee',
            'invited_by' => $inviter->id,
        ]);

        $showResponse = $this->get("/invitations/{$invitation->token}");
        $showResponse->assertOk();

        $acceptResponse = $this->post("/invitations/{$invitation->token}", [
            'name' => 'Alice Engineer',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $acceptResponse->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::where('email', 'engineer@acme-corp.test')->first();
        $this->assertNotNull($user);
        $this->assertEquals($org->id, $user->organization_id);
        $this->assertEquals('employee', $user->role);
        $this->assertTrue($user->hasCompletedOnboarding());

        $this->assertNotNull($invitation->fresh()->accepted_at);
    }
}
