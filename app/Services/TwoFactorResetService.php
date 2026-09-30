<?php

namespace App\Services;

use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class TwoFactorResetService
{
    /**
     * Panjang karakter kode OTP (6-digit angka).
     */
    public const OTP_LENGTH = 6;

    /**
     * Masa berlaku OTP dalam menit (10 menit).
     */
    public const VALIDITY_MINUTES = 10;

    /**
     * Batas maksimal kegagalan percobaan verifikasi sebelum dikunci (anti-bruteforce).
     */
    public const MAX_VERIFY_ATTEMPTS = 5;

    /**
     * Durasi lockout jika melebihi batas percobaan verifikasi salah (15 menit = 900 detik).
     */
    public const VERIFY_LOCKOUT_SECONDS = 900;

    /**
     * Jeda waktu minimum (cooldown) antar permintaan pengiriman ulang OTP (60 detik).
     */
    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Batas maksimal permintaan OTP per pengguna dalam kurun waktu 1 jam.
     */
    public const MAX_REQUESTS_PER_HOUR = 5;

    /**
     * Generate kode acak 6-digit yang aman secara kriptografis (CSPRNG).
     */
    public function generateSecureOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    /**
     * Membuat dan menyimpan kode OTP 2FA baru ke database dengan validasi rate limiting.
     *
     * @throws ValidationException
     */
    public function createOtp(User $user, ?string $ipAddress = null, ?string $userAgent = null): string
    {
        $this->ensureNotRateLimitedForRequest($user);

        // Nonaktifkan OTP aktif sebelumnya milik pengguna ini
        $this->invalidatePreviousOtps($user);

        $rawOtp = $this->generateSecureOtp();

        PasswordResetOtp::create([
            'user_id' => $user->id,
            'otp' => hash('sha256', $rawOtp),
            'expired_at' => now()->addMinutes(self::VALIDITY_MINUTES),
            'is_used' => false,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        // Catat hit rate limiter untuk jeda cooldown dan batas per jam
        RateLimiter::hit($this->getSendCooldownKey($user), self::RESEND_COOLDOWN_SECONDS);
        RateLimiter::hit($this->getSendHourlyKey($user), 3600);

        return $rawOtp;
    }

    /**
     * Memverifikasi kode OTP pengguna dengan proteksi rate limiting anti-bruteforce.
     * Melempar ValidationException jika tidak valid atau terblokir.
     *
     * @throws ValidationException
     */
    public function verify(User $user, string $inputOtp): bool
    {
        $this->ensureNotRateLimitedForVerification($user);

        $latestOtp = $this->getActiveOtp($user);

        if (! $latestOtp || ! $this->matchesOtp($latestOtp->otp, $inputOtp)) {
            RateLimiter::hit($this->getVerifyThrottleKey($user), self::VERIFY_LOCKOUT_SECONDS);
            $remaining = $this->getRemainingVerifyAttempts($user);

            if ($remaining > 0) {
                throw ValidationException::withMessages([
                    'otp' => "Kode OTP salah atau telah kedaluwarsa. Sisa percobaan: {$remaining}.",
                ]);
            }

            $seconds = $this->getVerifyLockoutRemainingSeconds($user);
            throw ValidationException::withMessages([
                'otp' => "Batas percobaan terlampaui. Akses diverifikasi dikunci selama {$seconds} detik demi keamanan (anti-bruteforce).",
            ]);
        }

        // Tandai OTP telah digunakan
        $latestOtp->update([
            'is_used' => true,
            'used_at' => now(),
        ]);

        // Bersihkan batas percobaan verifikasi jika sukses
        RateLimiter::clear($this->getVerifyThrottleKey($user));

        return true;
    }

    /**
     * Memverifikasi OTP tanpa melempar exception (mengembalikan array status).
     *
     * @return array{success: bool, message: string, remaining_attempts: int, lockout_seconds: int}
     */
    public function attempt(User $user, string $inputOtp): array
    {
        try {
            $this->verify($user, $inputOtp);

            return [
                'success' => true,
                'message' => 'Verifikasi OTP berhasil.',
                'remaining_attempts' => self::MAX_VERIFY_ATTEMPTS,
                'lockout_seconds' => 0,
            ];
        } catch (ValidationException $e) {
            return [
                'success' => false,
                'message' => $e->errors()['otp'][0] ?? 'Verifikasi OTP gagal.',
                'remaining_attempts' => $this->getRemainingVerifyAttempts($user),
                'lockout_seconds' => $this->getVerifyLockoutRemainingSeconds($user),
            ];
        }
    }

    /**
     * Mengecek kecocokan OTP baik hash SHA-256, Bcrypt, maupun plaintext.
     */
    public function matchesOtp(string $storedOtp, string $inputOtp): bool
    {
        // 1. Cek SHA-256 (default penyimpanan service)
        if (hash_equals($storedOtp, hash('sha256', $inputOtp))) {
            return true;
        }

        // 2. Cek plaintext (jika di-input manual di database saat dev)
        if (hash_equals($storedOtp, $inputOtp)) {
            return true;
        }

        // 3. Cek Bcrypt jika di-hash menggunakan Hash::make()
        if (strlen($storedOtp) === 60 && Hash::check($inputOtp, $storedOtp)) {
            return true;
        }

        return false;
    }

    /**
     * Mengambil OTP aktif yang belum kedaluwarsa dan belum digunakan.
     */
    public function getActiveOtp(User $user): ?PasswordResetOtp
    {
        return $user->passwordResetOtps()
            ->where('is_used', false)
            ->where('expired_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * Membatalkan seluruh OTP sebelumnya yang belum digunakan.
     */
    public function invalidatePreviousOtps(User $user): void
    {
        $user->passwordResetOtps()
            ->where('is_used', false)
            ->update([
                'is_used' => true,
                'used_at' => now(),
            ]);
    }

    /**
     * Memastikan permintaan OTP tidak melebihi cooldown atau batas per jam.
     *
     * @throws ValidationException
     */
    public function ensureNotRateLimitedForRequest(User $user): void
    {
        if (RateLimiter::tooManyAttempts($this->getSendCooldownKey($user), 1)) {
            $seconds = RateLimiter::availableIn($this->getSendCooldownKey($user));
            throw ValidationException::withMessages([
                'otp' => "Mohon tunggu {$seconds} detik sebelum meminta kode OTP kembali.",
            ]);
        }

        if (RateLimiter::tooManyAttempts($this->getSendHourlyKey($user), self::MAX_REQUESTS_PER_HOUR)) {
            $seconds = RateLimiter::availableIn($this->getSendHourlyKey($user));
            $minutes = (int) ceil($seconds / 60);
            throw ValidationException::withMessages([
                'otp' => "Batas permintaan OTP per jam telah tercapai. Silakan coba kembali dalam {$minutes} menit.",
            ]);
        }
    }

    /**
     * Memastikan percobaan verifikasi pengguna tidak sedang terkunci.
     *
     * @throws ValidationException
     */
    public function ensureNotRateLimitedForVerification(User $user): void
    {
        if ($this->isVerifyThrottled($user)) {
            $seconds = $this->getVerifyLockoutRemainingSeconds($user);
            throw ValidationException::withMessages([
                'otp' => "Akun terkunci sementara karena terlalu banyak percobaan salah. Coba lagi dalam {$seconds} detik.",
            ]);
        }
    }

    /**
     * Apakah percobaan verifikasi sedang terkena rate limit / lockout.
     */
    public function isVerifyThrottled(User $user): bool
    {
        return RateLimiter::tooManyAttempts($this->getVerifyThrottleKey($user), self::MAX_VERIFY_ATTEMPTS);
    }

    /**
     * Mendapatkan sisa percobaan verifikasi sebelum terkunci.
     */
    public function getRemainingVerifyAttempts(User $user): int
    {
        return RateLimiter::remaining($this->getVerifyThrottleKey($user), self::MAX_VERIFY_ATTEMPTS);
    }

    /**
     * Sisa durasi penalti lockout verifikasi dalam detik.
     */
    public function getVerifyLockoutRemainingSeconds(User $user): int
    {
        return RateLimiter::availableIn($this->getVerifyThrottleKey($user));
    }

    /**
     * Sisa durasi cooldown pengiriman ulang dalam detik.
     */
    public function getSendCooldownRemainingSeconds(User $user): int
    {
        return RateLimiter::availableIn($this->getSendCooldownKey($user));
    }

    /**
     * Membersihkan seluruh rate limit (biasa digunakan pada pengujian / reset admin).
     */
    public function clearAllThrottle(User $user): void
    {
        RateLimiter::clear($this->getVerifyThrottleKey($user));
        RateLimiter::clear($this->getSendCooldownKey($user));
        RateLimiter::clear($this->getSendHourlyKey($user));
    }

    /**
     * Throttle key untuk batasan percobaan verifikasi anti-bruteforce.
     */
    protected function getVerifyThrottleKey(User $user): string
    {
        return '2fa_verify:'.$user->id;
    }

    /**
     * Throttle key untuk jeda cooldown permintaan OTP (1 menit).
     */
    protected function getSendCooldownKey(User $user): string
    {
        return '2fa_send_cooldown:'.$user->id;
    }

    /**
     * Throttle key untuk kuota permintaan OTP per jam.
     */
    protected function getSendHourlyKey(User $user): string
    {
        return '2fa_send_hourly:'.$user->id;
    }
}
