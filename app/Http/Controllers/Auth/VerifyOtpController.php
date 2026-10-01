<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorResetService;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

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
        // Ambil email dari query string atau session
        $email = $request->query('email') ?? session('email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/ForgotPassword', [
            'email' => $email,
            'status' => session('status'),
            'step' => 2,
            'token' => '',
        ]);
    }

    /**
     * Verifikasi OTP dan generate Reset Token.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
        ], [
            'otp.required' => 'Harap masukkan 6 digit kode keamanan dengan lengkap.',
            'otp.size' => 'Kode OTP harus terdiri dari 6 digit angka.',
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Eksekusi verifikasi via TwoFactorResetService
        try {
            $this->otpService->verify($user, $request->otp);
        } catch (ValidationException $e) {
            return redirect()->route('password.verify', ['email' => $user->email])
                ->withErrors($e->errors());
        }

        // 2. Jika valid, buat token reset resmi Laravel
        /** @var PasswordBroker $broker */
        $broker = Password::broker();
        $token = $broker->createToken($user);

        // 3. Arahkan ke form ganti password (Step 3)
        return redirect()->route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ])->with('status', 'Kode 2FA berhasil diverifikasi. Silakan tentukan kata sandi baru Anda.');
    }
}
