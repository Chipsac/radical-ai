<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $org = Organization::create([
            'name' => 'Secure Co', 'slug' => 'secure-co', 'task_seq' => 0,
            'onboarding_completed_at' => now(),
        ]);

        return User::factory()->create([
            'organization_id' => $org->id,
            'password' => Hash::make('password'),
        ]);
    }

    private function enableFor(User $user): string
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(['AAAA1111-BBBB2222'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $secret;
    }

    public function test_setup_requires_a_valid_code_before_enabling(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get(route('two-factor.show'))->assertOk();

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_a_correct_code_enables_two_factor_and_issues_recovery_codes(): void
    {
        $user = $this->user();
        $totp = app(TotpService::class);

        // Mirror the controller: the pending secret lives in the session.
        $this->actingAs($user)->get(route('two-factor.show'));
        $secret = session('2fa.pending_secret');
        $this->assertNotEmpty($secret);

        $code = $totp->at($secret, intdiv(time(), 30));

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => $code])
            ->assertRedirect(route('two-factor.show'));

        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertNotEmpty($user->recoveryCodes());
    }

    public function test_login_stops_at_the_challenge_when_two_factor_is_on(): void
    {
        $user = $this->user();
        $this->enableFor($user);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.challenge'));

        // Password alone must not authenticate the session.
        $this->assertGuest();
    }

    public function test_a_valid_totp_completes_login(): void
    {
        $user = $this->user();
        $secret = $this->enableFor($user);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $code = app(TotpService::class)->at($secret, intdiv(time(), 30));

        $this->post(route('two-factor.challenge'), ['code' => $code])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_an_invalid_code_is_rejected(): void
    {
        $user = $this->user();
        $this->enableFor($user);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->post(route('two-factor.challenge'), ['code' => '111111'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_a_recovery_code_works_once_and_is_then_consumed(): void
    {
        $user = $this->user();
        $this->enableFor($user);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post(route('two-factor.challenge'), ['code' => 'AAAA1111-BBBB2222'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertEmpty($user->fresh()->recoveryCodes(), 'A used recovery code must be discarded.');
    }

    public function test_disabling_requires_the_current_password(): void
    {
        $user = $this->user();
        $this->enableFor($user);

        $this->actingAs($user)
            ->delete(route('two-factor.destroy'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $this->actingAs($user)->delete(route('two-factor.destroy'), ['password' => 'password']);

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }
}
