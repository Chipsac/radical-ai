<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    private function contactPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Dana Whelan',
            'email' => 'dana@example.com',
            'company' => 'Whelan & Co',
            'phone' => '+353 91 123456',
            'topic' => 'demo',
            'message' => 'We are a team of twelve and would like to see a demo.',
            'consent' => '1',
        ], $overrides);
    }

    /** The payload minus form-only fields, for creating a record directly. */
    private function storedPayload(array $overrides = []): array
    {
        $data = $this->contactPayload($overrides);
        unset($data['consent']);

        return $data + ['ip_address' => '127.0.0.1'];
    }

    // ---- The page itself -----------------------------------------------

    public function test_the_landing_page_is_public(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_it_shows_signup_and_login_calls_to_action(): void
    {
        $response = $this->get('/');

        $response->assertSee(route('register'));
        $response->assertSee(route('login'));
        $response->assertSee('Start free trial');
        $response->assertSee('Log in');
    }

    public function test_it_covers_the_product_and_its_sections(): void
    {
        $response = $this->get('/');

        // Every module is represented
        $response->assertSee('CRM');
        $response->assertSee('Daily tracker');
        $response->assertSee('HR &amp; payslips', false);

        // And the main sections exist
        foreach (['id="features"', 'id="how"', 'id="pricing"', 'id="faq"', 'id="contact"'] as $anchor) {
            $response->assertSee($anchor, false);
        }
    }

    public function test_signed_in_users_are_sent_to_their_workspace(): void
    {
        $org = Organization::create([
            'name' => 'Co', 'slug' => 'co', 'task_seq' => 0,
            'onboarding_completed_at' => now(),
        ]);
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get('/')->assertRedirect(route('home'));
    }

    // ---- Contact form ---------------------------------------------------

    public function test_a_valid_enquiry_is_stored_and_emailed(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), $this->contactPayload())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('contact_sent');

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'dana@example.com',
            'topic' => 'demo',
        ]);

        Mail::assertSent(ContactMessageMail::class);
    }

    public function test_the_success_message_is_shown_after_sending(): void
    {
        Mail::fake();

        $this->followingRedirects()
            ->post(route('contact.store'), $this->contactPayload())
            ->assertSee('message received');
    }

    public function test_required_fields_are_validated(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'message', 'consent']);

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_a_malformed_email_is_rejected(): void
    {
        $this->post(route('contact.store'), $this->contactPayload(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');
    }

    public function test_a_very_short_message_is_rejected(): void
    {
        $this->post(route('contact.store'), $this->contactPayload(['message' => 'hi']))
            ->assertSessionHasErrors('message');
    }

    public function test_consent_must_be_given(): void
    {
        $this->post(route('contact.store'), $this->contactPayload(['consent' => null]))
            ->assertSessionHasErrors('consent');
    }

    /**
     * A bot that fills every field gets a success response but writes nothing,
     * so it learns nothing about how it was caught.
     */
    public function test_the_honeypot_silently_discards_bot_submissions(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), $this->contactPayload(['website' => 'http://spam.example']))
            ->assertSessionHas('contact_sent');

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_repeat_submissions_from_one_address_are_throttled(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('contact.store'), $this->contactPayload(['email' => "sender{$i}@example.com"]))
                ->assertSessionHasNoErrors();
        }

        // The sixth within the hour is refused.
        $this->post(route('contact.store'), $this->contactPayload(['email' => 'sender6@example.com']))
            ->assertSessionHasErrors('message');

        $this->assertSame(5, ContactMessage::count());
    }

    public function test_the_notification_email_carries_the_enquiry_details(): void
    {
        $message = ContactMessage::create($this->storedPayload());

        $rendered = (new ContactMessageMail($message))->render();

        $this->assertStringContainsString('Dana Whelan', $rendered);
        $this->assertStringContainsString('dana@example.com', $rendered);
        $this->assertStringContainsString('would like to see a demo', $rendered);
    }

    public function test_replying_to_the_notification_reaches_the_enquirer(): void
    {
        $message = ContactMessage::create($this->storedPayload());

        $envelope = (new ContactMessageMail($message))->envelope();

        $this->assertSame('dana@example.com', $envelope->replyTo[0]->address);
    }
}
