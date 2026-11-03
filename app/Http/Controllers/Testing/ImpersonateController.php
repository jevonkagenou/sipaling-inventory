<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    /**
     * Beralih identitas sebagai pengguna / peran tertentu (Khusus Lingkungan Pengujian & Lokal).
     */
    public function take(Request $request): RedirectResponse
    {
        if (! app()->environment('local', 'testing')) {
            abort(403, 'Fitur Impersonate dinonaktifkan di luar lingkungan pengujian.');
        }

        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'role' => ['nullable', 'string', 'in:komisaris,manajer-operasional,staf-gudang,auditor-internal'],
        ]);

        $targetUser = null;

        if (! empty($validated['user_id'])) {
            $targetUser = User::find($validated['user_id']);
        } elseif (! empty($validated['role'])) {
            $targetUser = User::role($validated['role'])->where('is_active', true)->first();
        }

        if (! $targetUser) {
            return back()->with('error', 'Target akun pengguna untuk impersonate tidak ditemukan.');
        }

        // Simpan akun asli jika belum sedang impersonate
        if (! session()->has('original_user_id') && Auth::check()) {
            session(['original_user_id' => Auth::id()]);
        }

        Auth::login($targetUser);

        $roleLabel = match ($validated['role'] ?? $targetUser->roles->first()?->name) {
            'komisaris' => 'Komisaris',
            'manajer-operasional' => 'Manajer Operasional',
            'staf-gudang' => 'Staf Gudang',
            'auditor-internal' => 'Auditor Internal',
            default => 'Pengguna',
        };

        return back()->with('success', "Mode Pengujian: Berhasil beralih identitas sebagai {$targetUser->name} ({$roleLabel}).");
    }

    /**
     * Mengembalikan identitas ke akun asli sebelum impersonate.
     */
    public function leave(Request $request): RedirectResponse
    {
        if (! app()->environment('local', 'testing')) {
            abort(403, 'Aksi terlarang di luar lingkungan pengujian.');
        }

        if (session()->has('original_user_id')) {
            $originalUser = User::find(session('original_user_id'));
            if ($originalUser) {
                Auth::login($originalUser);
            }
            session()->forget('original_user_id');

            return back()->with('success', "Kembali ke akun awal ({$originalUser?->name}).");
        }

        return back()->with('message', 'Tidak ada sesi impersonate aktif.');
    }
}
