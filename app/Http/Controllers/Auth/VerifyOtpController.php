<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;

class VerifyOtpController extends Controller
{
    public function __construct(
        protected TwoFactorResetService $otpService
    ) {}

    /**
     * Tampilkan halaman input OTP.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        // Pastikan email ada dari session sebelumnya
        $email = session('email') ?? $request->query('email');

        if (!$email) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/VerifyOtp', [
            'email' => $email,
            'status' => session('status'),
        ]);
    }

    /**
     * Verifikasi OTP dan generate Reset Token.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Verifikasi OTP (Otomatis melempar ValidationException jika salah/bruteforce)
        $this->otpService->verify($user, $request->otp);

        // 2. Jika valid, buat token reset bawaan Laravel
        /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
        $broker = Password::broker();
        $token = $broker->createToken($user);

        // 3. Arahkan langsung ke form ganti password bawaan Breeze
        return redirect()->route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);
    }
}
