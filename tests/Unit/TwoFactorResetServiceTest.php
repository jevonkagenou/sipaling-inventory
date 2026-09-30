<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\TwoFactorResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TwoFactorResetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TwoFactorResetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TwoFactorResetService;
    }

    public function test_it_generates_cryptographically_secure_six_digit_otp(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $otp = $this->service->generateSecureOtp();

            $this->assertIsString($otp);
            $this->assertSame(6, strlen($otp));
            $this->assertMatchesRegularExpression('/^[1-9][0-9]{5}$/', $otp);
            $this->assertGreaterThanOrEqual(100000, (int) $otp);
            $this->assertLessThanOrEqual(999999, (int) $otp);
        }
    }

    public function test_it_creates_otp_record_and_returns_raw_code(): void
    {
        $user = User::factory()->create();

        $rawOtp = $this->service->createOtp($user, '127.0.0.1', 'PHPUnit Test');

        $this->assertSame(6, strlen($rawOtp));
        $this->assertDatabaseHas('password_reset_otps', [
            'user_id' => $user->id,
            'otp' => hash('sha256', $rawOtp),
            'is_used' => false,
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_it_successfully_verifies_valid_otp(): void
    {
        $user = User::factory()->create();
        $rawOtp = $this->service->createOtp($user);

        $result = $this->service->verify($user, $rawOtp);

        $this->assertTrue($result);
        $this->assertDatabaseHas('password_reset_otps', [
            'user_id' => $user->id,
            'is_used' => true,
        ]);
    }

    public function test_it_enforces_anti_bruteforce_rate_limiting_after_max_failed_attempts(): void
    {
        $user = User::factory()->create();
        $this->service->clearAllThrottle($user);
        $this->service->createOtp($user);

        // 4 failed attempts should throw validation exception with remaining count
        for ($attempt = 1; $attempt < TwoFactorResetService::MAX_VERIFY_ATTEMPTS; $attempt++) {
            try {
                $this->service->verify($user, '000000');
                $this->fail('Harusnya melempar ValidationException.');
            } catch (ValidationException $e) {
                $this->assertStringContainsString('Sisa percobaan', $e->errors()['otp'][0]);
            }
        }

        // 5th failed attempt should trigger lockout
        try {
            $this->service->verify($user, '000000');
            $this->fail('Harusnya melempar ValidationException untuk batas terlampaui.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Batas percobaan terlampaui', $e->errors()['otp'][0]);
        }

        // Subsequent attempt should be locked out
        $this->assertTrue($this->service->isVerifyThrottled($user));
        $this->expectException(ValidationException::class);
        $this->service->verify($user, '000000');
    }

    public function test_it_enforces_resend_cooldown_rate_limiting(): void
    {
        $user = User::factory()->create();
        $this->service->clearAllThrottle($user);

        // First request succeeds
        $this->service->createOtp($user);

        // Immediate second request within 60s should be rejected with cooldown message
        $this->expectException(ValidationException::class);
        $this->service->createOtp($user);
    }
}
