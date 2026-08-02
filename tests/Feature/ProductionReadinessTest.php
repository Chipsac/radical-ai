<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the things that make the app safe to expose publicly.
 */
class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    // ---- Legal pages ----------------------------------------------------

    public function test_the_terms_page_is_public(): void
    {
        $this->get('/terms')->assertOk()->assertSee('Terms of Service');
    }

    public function test_the_privacy_page_is_public(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Privacy Policy');
    }

    /**
     * The signup consent checkbox claims the user agrees to these documents.
     * If the links do not resolve, that consent is meaningless.
     */
    public function test_the_signup_consent_links_resolve(): void
    {
        $response = $this->get('/register');

        $response->assertSee(route('legal.terms'));
        $response->assertSee(route('legal.privacy'));

        $this->get(route('legal.terms'))->assertOk();
        $this->get(route('legal.privacy'))->assertOk();
    }

    public function test_the_privacy_policy_covers_payslips_and_tenant_separation(): void
    {
        $response = $this->get('/privacy');

        $response->assertSee('Payslips', false);
        $response->assertSee('fail closed', false);
        $response->assertSee('GDPR', false);
    }

    // ---- Security headers ------------------------------------------------

    public function test_security_headers_are_applied(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotNull($response->headers->get('Content-Security-Policy'));
        $this->assertNotNull($response->headers->get('Permissions-Policy'));
    }

    public function test_the_content_security_policy_forbids_framing_and_objects(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
    }

    /**
     * The middleware strips this from the response bag, but PHP also emits it
     * at the SAPI level, which middleware cannot reach. Production covers that
     * separately: `expose_php = Off` in docker/php.ini and
     * `fastcgi_hide_header X-Powered-By` in docker/nginx.conf. Under
     * `artisan serve` locally you will still see it.
     */
    public function test_the_framework_does_not_advertise_itself(): void
    {
        $this->assertNull($this->get('/')->headers->get('X-Powered-By'));

        $this->assertStringContainsString('expose_php = Off', file_get_contents(base_path('docker/php.ini')));
        $this->assertStringContainsString('fastcgi_hide_header X-Powered-By', file_get_contents(base_path('docker/nginx.conf')));
    }

    // ---- Health probes ---------------------------------------------------

    public function test_the_liveness_probe_is_cheap_and_public(): void
    {
        $this->get('/healthz')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_the_readiness_probe_checks_real_dependencies(): void
    {
        $response = $this->get('/health');

        $response->assertOk()->assertJsonPath('status', 'ok');

        foreach (['database', 'cache', 'storage'] as $dependency) {
            $response->assertJsonPath("checks.{$dependency}.ok", true);
        }
    }

    /**
     * This endpoint is unauthenticated, so a failure must not echo connection
     * strings or credentials back to the caller.
     */
    public function test_the_readiness_probe_does_not_leak_details(): void
    {
        $body = $this->get('/health')->getContent();

        $this->assertStringNotContainsString('password', strtolower($body));
        $this->assertStringNotContainsString('secret', strtolower($body));
    }
}
