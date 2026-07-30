<?php

namespace Tests\Unit;

use App\Services\TotpService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TotpServiceTest extends TestCase
{
    private TotpService $totp;

    /** Base32 of the ASCII secret "12345678901234567890" used by RFC 6238. */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new TotpService;
    }

    /**
     * RFC 6238 Appendix B test vectors (SHA-1). The published values are
     * 8 digits; we emit 6, so compare against the low 6 digits.
     */
    public static function rfcVectors(): array
    {
        return [
            'T=59' => [59, '287082'],
            'T=1111111109' => [1111111109, '081804'],
            'T=1111111111' => [1111111111, '050471'],
            'T=1234567890' => [1234567890, '005924'],
            'T=2000000000' => [2000000000, '279037'],
        ];
    }

    #[DataProvider('rfcVectors')]
    public function test_matches_rfc6238_test_vectors(int $timestamp, string $expected): void
    {
        $counter = intdiv($timestamp, 30);

        $this->assertSame(
            $expected,
            $this->totp->at(self::RFC_SECRET, $counter),
            "TOTP output disagrees with RFC 6238 vector at T={$timestamp}"
        );
    }

    public function test_verifies_current_code(): void
    {
        $secret = $this->totp->generateSecret();
        $code = $this->totp->at($secret, intdiv(time(), 30));

        $this->assertTrue($this->totp->verify($secret, $code));
    }

    public function test_accepts_one_period_of_clock_drift(): void
    {
        $secret = $this->totp->generateSecret();
        $now = time();

        $previous = $this->totp->at($secret, intdiv($now, 30) - 1);
        $next = $this->totp->at($secret, intdiv($now, 30) + 1);

        $this->assertTrue($this->totp->verify($secret, $previous, $now));
        $this->assertTrue($this->totp->verify($secret, $next, $now));
    }

    public function test_rejects_codes_beyond_the_drift_window(): void
    {
        $secret = $this->totp->generateSecret();
        $now = time();

        $stale = $this->totp->at($secret, intdiv($now, 30) - 5);

        $this->assertFalse($this->totp->verify($secret, $stale, $now));
    }

    public function test_rejects_malformed_codes(): void
    {
        $secret = $this->totp->generateSecret();

        $this->assertFalse($this->totp->verify($secret, ''));
        $this->assertFalse($this->totp->verify($secret, '12345'));
        $this->assertFalse($this->totp->verify($secret, 'abcdef'));
    }

    public function test_generated_secrets_are_base32_and_unique(): void
    {
        $a = $this->totp->generateSecret();
        $b = $this->totp->generateSecret();

        $this->assertSame(32, strlen($a));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $a);
        $this->assertNotSame($a, $b);
    }

    public function test_provisioning_uri_is_well_formed(): void
    {
        $uri = $this->totp->provisioningUri('ABCDEFGHIJKLMNOP', 'jo@acme.test', 'Radical AI');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=ABCDEFGHIJKLMNOP', $uri);
        $this->assertStringContainsString('issuer=Radical%20AI', $uri);
        $this->assertStringContainsString('digits=6', $uri);
    }

    public function test_recovery_codes_are_unique(): void
    {
        $codes = $this->totp->generateRecoveryCodes(8);

        $this->assertCount(8, $codes);
        $this->assertCount(8, array_unique($codes));
    }
}
