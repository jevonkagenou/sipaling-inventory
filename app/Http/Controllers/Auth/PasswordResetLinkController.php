<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Buat OTP (Otomatis ditangani rate-limit & cooldown dari Service)
        $otp = $this->otpService->createOtp($user, $request->ip(), $request->userAgent());

        // 2. TODO: Kirim email/WA OTP di sini. Contoh:
        // Mail::to($user)->send(new SendOtpMail($otp));

        // Log untuk tahap development (sekarang memanggil dari facade yang di-import)
        Log::info("OTP Lupa Password untuk {$user->email}: {$otp}");

        // 3. Arahkan ke halaman verifikasi OTP dengan membawa email di session
        return redirect()->route('password.verify')->with([
            'email' => $user->email,
            'status' => 'Kode OTP telah dikirim ke email Anda.'
        ]);
    }
}
