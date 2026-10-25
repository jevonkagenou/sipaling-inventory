<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * Mengunduh berkas template CSV untuk impor massal pengguna.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_pengguna_sipaling.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            // Menulis UTF-8 BOM untuk kompatibilitas Microsoft Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // Header Kolom
            fputcsv($handle, ['name', 'email', 'phone', 'role', 'password']);

            // Baris Data Contoh
            fputcsv($handle, ['Ahmad Fauzi', 'ahmad.fauzi@sipaling.com', '081234567890', 'staf-gudang', 'Password123!']);
            fputcsv($handle, ['Budi Santoso', 'budi.santoso@sipaling.com', '081298765432', 'manajer-operasional', 'Password123!']);
            fputcsv($handle, ['Citra Lestari', 'citra.lestari@sipaling.com', '081311223344', 'auditor-internal', 'Password123!']);
            fputcsv($handle, ['Dewi Sartika', 'dewi.sartika@sipaling.com', '081355667788', 'komisaris', 'Password123!']);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Mengimpor data pengguna massal dari berkas CSV.
     */
    public function importCsv(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ], [
            'file.required' => 'Berkas CSV wajib dipilih.',
            'file.mimes' => 'Berkas harus berformat .csv atau teks berpemisah koma.',
            'file.max' => 'Ukuran berkas CSV maksimal 5 MB.',
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return back()->withErrors(['file' => 'Gagal membaca berkas CSV.'])->with('error', 'Gagal membaca berkas CSV.');
        }

        // Baca baris pertama (header) dan bersihkan BOM jika ada
        $firstLine = fgets($handle);
        if (! $firstLine) {
            fclose($handle);

            return back()->withErrors(['file' => 'Berkas CSV kosong.'])->with('error', 'Berkas CSV kosong.');
        }

        $firstLine = str_replace("\xEF\xBB\xBF", '', $firstLine);
        // Deteksi delimiter (koma atau titik koma)
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';
        $headerColumns = array_map(fn ($col) => strtolower(trim($col)), str_getcsv($firstLine, $delimiter));

        $colMap = array_flip($headerColumns);
        $requiredKeys = ['name', 'email', 'role'];

        foreach ($requiredKeys as $reqKey) {
            if (! isset($colMap[$reqKey])) {
                fclose($handle);

                return back()->withErrors(['file' => "Format CSV tidak valid. Kolom '{$reqKey}' tidak ditemukan pada baris judul."])
                    ->with('error', "Kolom wajib '{$reqKey}' tidak ditemukan pada berkas CSV.");
            }
        }

        $importedCount = 0;
        $skippedCount = 0;
        $existingEmails = User::pluck('email')->map(fn ($e) => strtolower(trim($e)))->flip()->toArray();

        // Valid roles cache
        $validRoles = Role::pluck('name')->mapWithKeys(function ($roleName) {
            return [strtolower($roleName) => $roleName];
        })->toArray();

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 4096, $delimiter)) !== false) {
                if (empty($row) || (count($row) === 1 && empty($row[0]))) {
                    continue;
                }

                $name = isset($colMap['name']) && isset($row[$colMap['name']]) ? trim($row[$colMap['name']]) : '';
                $email = isset($colMap['email']) && isset($row[$colMap['email']]) ? strtolower(trim($row[$colMap['email']])) : '';
                $rawRole = isset($colMap['role']) && isset($row[$colMap['role']]) ? trim($row[$colMap['role']]) : '';
                $phone = isset($colMap['phone']) && isset($row[$colMap['phone']]) ? trim($row[$colMap['phone']]) : null;
                $rawPassword = isset($colMap['password']) && isset($row[$colMap['password']]) ? trim($row[$colMap['password']]) : '';

                if (empty($name) || empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skippedCount++;

                    continue;
                }

                if (isset($existingEmails[$email])) {
                    $skippedCount++;

                    continue;
                }

                $normalizedRoleKey = str_replace(' ', '-', strtolower($rawRole));
                if (! isset($validRoles[$normalizedRoleKey])) {
                    $skippedCount++;

                    continue;
                }
                $assignedRole = $validRoles[$normalizedRoleKey];

                $password = (! empty($rawPassword) && strlen($rawPassword) >= 8)
                    ? $rawPassword
                    : 'Password123!';

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => ! empty($phone) ? $phone : null,
                    'password' => Hash::make($password),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);

                $user->assignRole($assignedRole);

                $existingEmails[$email] = true;
                $importedCount++;
            }

            fclose($handle);

            ActivityLog::create([
                'log_name' => 'users',
                'description' => "Mengimpor {$importedCount} pengguna baru dari berkas CSV ({$file->getClientOriginalName()})",
                'subject_type' => User::class,
                'causer_type' => Auth::user() ? get_class(Auth::user()) : null,
                'causer_id' => Auth::id(),
                'event' => 'imported',
                'properties' => [
                    'imported_count' => $importedCount,
                    'skipped_count' => $skippedCount,
                    'filename' => $file->getClientOriginalName(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            DB::commit();

            $message = "Proses impor selesai: {$importedCount} pengguna berhasil ditambahkan.";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} baris dilewati karena duplikat atau format tidak lengkap).";
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'imported_count' => $importedCount,
                    'skipped_count' => $skippedCount,
                ]);
            }

            return redirect()->route('users.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }

            return back()->withErrors(['file' => 'Gagal memproses berkas CSV: '.$e->getMessage()])
                ->with('error', 'Terjadi kesalahan saat menyimpan data pengguna.');
        }
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
                ->withErrors(['general' => 'Gagal menyimpan pengguna: '.$e->getMessage()]);
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
                ->withErrors(['general' => 'Gagal memperbarui pengguna: '.$e->getMessage()]);
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
