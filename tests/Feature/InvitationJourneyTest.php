<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Steps 6-11 of the customer journey: invite → email → accept → log in →
 * use only the modules that were granted.
 */
class InvitationJourneyTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'manager', 'employee'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->org = Organization::create([
            'name' => 'Journey Co', 'slug' => 'journey-co', 'task_seq' => 0,
            'onboarding_completed_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'organization_id' => $this->org->id,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('admin');
    }

    private function invite(array $modules = [Modules::CRM]): Invitation
    {
        Mail::fake();

        $this->actingAs($this->admin)->post(route('invitations.store'), [
            'email' => 'newjoiner@journey.test',
            'role' => 'employee',
            'modules' => $modules,
        ])->assertSessionHasNoErrors();

        return Invitation::firstWhere('email', 'newjoiner@journey.test');
    }

    public function test_an_invitation_records_the_granted_modules(): void
    {
        $invitation = $this->invite([Modules::CRM]);

        $this->assertSame([Modules::CRM], $invitation->modules);
        $this->assertTrue($invitation->isPending());
    }

    public function test_the_invitation_email_is_sent_to_the_right_person(): void
    {
        Mail::fake();

        $this->actingAs($this->admin)->post(route('invitations.store'), [
            'email' => 'newjoiner@journey.test',
            'role' => 'employee',
            'modules' => [Modules::HR],
        ]);

        Mail::assertSent(InvitationMail::class, function (InvitationMail $mail) {
            return $mail->hasTo('newjoiner@journey.test')
                && $mail->organization->name === 'Journey Co';
        });
    }

    public function test_the_invitation_email_renders_with_a_working_link(): void
    {
        $invitation = $this->invite();

        $rendered = (new InvitationMail(
            $invitation,
            $this->org,
            $this->admin->name
        ))->render();

        $this->assertStringContainsString('Accept invitation', $rendered);
        $this->assertStringContainsString($invitation->token, $rendered);
        $this->assertStringContainsString('Journey Co', $rendered);
    }

    /**
     * Regression: email_verified_at must survive mass assignment. When it was
     * missing from $fillable it was silently dropped, and every invited user
     * landed on the "verify your email" wall with no way past it.
     */
    public function test_an_invited_user_is_already_verified_and_can_reach_the_app(): void
    {
        $invitation = $this->invite();

        $this->post(route('invitations.complete', $invitation->token), [
            'name' => 'Sam Doyle',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('home'));

        $user = User::firstWhere('email', 'newjoiner@journey.test');

        $this->assertTrue($user->hasVerifiedEmail(), 'An invited user must not be sent to the verification wall.');
        $this->actingAs($user)->get('/')->assertOk();
    }

    public function test_an_accepted_invitation_grants_exactly_the_chosen_modules(): void
    {
        $invitation = $this->invite([Modules::CRM]);

        $this->post(route('invitations.complete', $invitation->token), [
            'name' => 'Sam Doyle',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::firstWhere('email', 'newjoiner@journey.test');

        $this->assertSame([Modules::CRM], $user->allowedModules());

        $this->actingAs($user)->get('/crm/deals')->assertOk();
        $this->actingAs($user)->get('/tasks/board')->assertForbidden();
        $this->actingAs($user)->get('/hr')->assertForbidden();
    }

    public function test_a_manager_can_widen_access_after_the_employee_has_joined(): void
    {
        $invitation = $this->invite([Modules::CRM]);

        $this->post(route('invitations.complete', $invitation->token), [
            'name' => 'Sam Doyle',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::firstWhere('email', 'newjoiner@journey.test');
        $this->actingAs($user)->get('/hr')->assertForbidden();

        // Step 10: the manager grants HR as well.
        $this->actingAs($this->admin)->patch(route('team.modules', $user), [
            'modules' => [Modules::CRM, Modules::HR],
        ])->assertSessionHasNoErrors();

        $this->actingAs($user->fresh())->get('/hr')->assertOk();
    }

    public function test_an_expired_invitation_is_refused(): void
    {
        $invitation = $this->invite();
        $invitation->forceFill(['expires_at' => now()->subDay()])->save();

        $this->get(route('invitations.accept', $invitation->token))
            ->assertSee('Invitation not valid');

        $this->post(route('invitations.complete', $invitation->token), [
            'name' => 'Sam Doyle',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(410);
    }

    public function test_an_invitation_cannot_be_used_twice(): void
    {
        $invitation = $this->invite();

        $payload = [
            'name' => 'Sam Doyle',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->post(route('invitations.complete', $invitation->token), $payload);
        $this->post('/logout');

        $this->post(route('invitations.complete', $invitation->token), $payload)
            ->assertStatus(410);

        $this->assertSame(1, User::where('email', 'newjoiner@journey.test')->count());
    }
}
