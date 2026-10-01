<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $roleFilter = $request->input('role');
        $statusFilter = $request->input('status'); // 'all', '1' (active), '0' (inactive)

        $query = User::query()
            ->with(['roles:id,name,guard_name'])
            ->latest('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($roleFilter && $roleFilter !== 'all') {
            $query->whereHas('roles', function ($q) use ($roleFilter) {
                $q->where('name', $roleFilter);
            });
        }

        if ($statusFilter !== null && $statusFilter !== '' && $statusFilter !== 'all') {
            $query->where('is_active', $statusFilter === '1' || $statusFilter === 'active' || $statusFilter === true);
        }

        $users = $query->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => (bool) $user->is_active,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at?->toIso8601String(),
                'roles' => $user->roles->pluck('name')->toArray(),
                'primary_role' => $user->roles->first()?->name ?? 'user',
            ];
        });

        // Statistics for KPI Cards
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();

        $roleCounts = [
            'komisaris' => User::role('komisaris')->count(),
            'manajer-operasional' => User::role('manajer-operasional')->count(),
            'staf-gudang' => User::role('staf-gudang')->count(),
            'auditor-internal' => User::role('auditor-internal')->count(),
        ];

        $availableRoles = Role::select('id', 'name')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'label' => match ($role->name) {
                    'komisaris' => 'Komisaris',
                    'manajer-operasional' => 'Manajer Operasional',
                    'staf-gudang' => 'Staf Gudang',
                    'auditor-internal' => 'Auditor Internal',
                    default => ucfirst(str_replace('-', ' ', $role->name)),
                },
            ];
        });

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $availableRoles,
            'filters' => [
                'search' => $search ?? '',
                'role' => $roleFilter ?? 'all',
                'status' => $statusFilter ?? 'all',
            ],
            'stats' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'role_counts' => $roleCounts,
            ],
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]*$/'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar dalam sistem.',
            'phone.regex' => 'Format nomor telepon tidak valid (hanya angka dan simbol +, -, () yang diizinkan).',
            'role.required' => 'Peran pengguna wajib dipilih.',
            'role.exists' => 'Peran yang dipilih tidak terdaftar di sistem.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => strtolower($validated['email']),
                    'phone' => $validated['phone'] ?? null,
                    'password' => Hash::make($validated['password']),
                    'is_active' => $validated['is_active'] ?? true,
                    'email_verified_at' => now(),
                ]);

                $user->assignRole($validated['role']);
            });

            return redirect()->route('users.index')
                ->with('success', "Pengguna {$validated['name']} berhasil didaftarkan.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => 'Gagal menyimpan pengguna: ' . $e->getMessage()]);
        }
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]*$/'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'password' => ['nullable', 'string', Password::min(8), 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar dalam sistem.',
            'phone.regex' => 'Format nomor telepon tidak valid.',
            'role.required' => 'Peran pengguna wajib dipilih.',
            'role.exists' => 'Peran yang dipilih tidak terdaftar di sistem.',
            'password.min' => 'Kata sandi minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        // Safety rule: Prevent user from deactivating themselves
        $isActive = $request->has('is_active') ? (bool) $request->input('is_active') : $user->is_active;
        if (Auth::id() === $user->id && ! $isActive) {
            throw ValidationException::withMessages([
                'is_active' => 'Anda tidak dapat menonaktifkan akun yang sedang Anda gunakan saat ini.',
            ]);
        }

        try {
            DB::transaction(function () use ($user, $validated, $isActive) {
                $updateData = [
                    'name' => $validated['name'],
                    'email' => strtolower($validated['email']),
                    'phone' => $validated['phone'] ?? null,
                    'is_active' => $isActive,
                ];

                if (! empty($validated['password'])) {
                    $updateData['password'] = Hash::make($validated['password']);
                }

                $user->update($updateData);
                $user->syncRoles([$validated['role']]);
            });

            return redirect()->route('users.index')
                ->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => 'Gagal memperbarui pengguna: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle the active status of the specified user.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        if (Auth::id() === $user->id) {
            return redirect()->back()->withErrors([
                'error' => 'Anda tidak dapat mengubah status aktivasi akun Anda sendiri.',
            ]);
        }

        // Safety check: Prevent deactivating the last active Komisaris
        if ($user->is_active && $user->hasRole('komisaris')) {
            $activeKomisarisCount = User::role('komisaris')->where('is_active', true)->count();
            if ($activeKomisarisCount <= 1) {
                return redirect()->back()->withErrors([
                    'error' => 'Tidak dapat menonaktifkan Komisaris terakhir yang masih aktif demi kelangsungan otorisasi sistem.',
                ]);
            }
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()->with('success', "Akun pengguna {$user->name} berhasil {$statusText}.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if (Auth::id() === $user->id) {
            return redirect()->back()->withErrors([
                'error' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ]);
        }

        if ($user->hasRole('komisaris')) {
            $komisarisCount = User::role('komisaris')->count();
            if ($komisarisCount <= 1) {
                return redirect()->back()->withErrors([
                    'error' => 'Tidak dapat menghapus Komisaris satu-satunya dalam sistem.',
                ]);
            }
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "Pengguna {$userName} berhasil dihapus dari sistem.");
    }
}
