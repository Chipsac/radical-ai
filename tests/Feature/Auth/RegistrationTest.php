<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'organization_name' => 'Test Company Ltd',
            'name' => 'Test User',
            'job_title' => 'Founder',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ], $overrides);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_signup_provisions_an_organization_and_owner(): void
    {
        $response = $this->post('/register', $this->validPayload());

        $this->assertAuthenticated();
        // Signup now lands on the "verify your email" notice (journey step 2).
        $response->assertRedirect(route('verification.notice'));

        $org = Organization::firstWhere('name', 'Test Company Ltd');
        $this->assertNotNull($org, 'The organization should have been created.');
        $this->assertSame('test-company-ltd', $org->slug);

        $user = User::firstWhere('email', 'test@example.com');
        $this->assertSame($org->id, $user->organization_id);
        $this->assertSame('admin', $user->role);
        $this->assertTrue($user->hasRole('admin'));
        $this->assertSame($user->id, $org->fresh()->owner_id);
    }

    public function test_signup_creates_baseline_reference_data(): void
    {
        $this->post('/register', $this->validPayload());

        $org = Organization::firstWhere('name', 'Test Company Ltd');

        // Leave types and task categories the app needs to be usable.
        $this->assertDatabaseHas('leave_types', ['organization_id' => $org->id, 'name' => 'Annual Leave']);
        $this->assertDatabaseHas('task_categories', ['organization_id' => $org->id, 'name' => 'Sales']);
        // The owner gets an employee record so HR works from day one.
        $this->assertDatabaseHas('employees', ['organization_id' => $org->id, 'job_title' => 'Founder']);
    }

    public function test_signup_requires_accepting_terms(): void
    {
        $response = $this->post('/register', $this->validPayload(['terms' => null]));

        $response->assertSessionHasErrors('terms');
        $this->assertGuest();
    }

    public function test_signup_requires_an_organization_name(): void
    {
        $response = $this->post('/register', $this->validPayload(['organization_name' => '']));

        $response->assertSessionHasErrors('organization_name');
        $this->assertGuest();
    }

    public function test_slugs_do_not_collide_between_organizations(): void
    {
        $this->post('/register', $this->validPayload());
        $this->post('/logout');

        $this->post('/register', $this->validPayload(['email' => 'second@example.com']));

        $slugs = Organization::where('name', 'Test Company Ltd')->pluck('slug');

        $this->assertCount(2, $slugs);
        $this->assertCount(2, $slugs->unique(), 'Each organization must get a unique slug.');
    }
}
