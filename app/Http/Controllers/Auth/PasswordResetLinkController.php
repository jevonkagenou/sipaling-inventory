<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordOtpMail;
use App\Models\User;
use App\Services\TwoFactorResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function __construct(
        protected TwoFactorResetService $otpService
    ) {}

    /**
     * Display the password reset link (OTP request) view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
            'step' => 1,
            'email' => '',
            'token' => '',
        ]);
    }

    /**
     * Handle an incoming password reset OTP request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Email operasional wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.exists' => 'Alamat email tidak terdaftar dalam sistem inventaris.',
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Buat OTP (Otomatis ditangani rate-limit & cooldown dari Service)
        $otp = $this->otpService->createOtp($user, $request->ip(), $request->userAgent());

        // 2. Kirim Email Resmi SIPALING
        try {
            Mail::to($user->email)->send(new ResetPasswordOtpMail($otp, $user, $request->ip()));
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim email OTP ke {$user->email}: ".$e->getMessage());
        }

        // Catat di log untuk kemudahan pengujian lokal
        Log::info("OTP 2FA Lupa Password untuk {$user->email}: {$otp}");

        // 3. Arahkan ke halaman verifikasi OTP dengan membawa email di query string dan session
        return redirect()->route('password.verify', ['email' => $user->email])->with([
            'email' => $user->email,
            'status' => 'Kode keamanan 6-digit telah dikirimkan ke email Anda.',
        ]);
    }
}
