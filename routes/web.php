<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => false,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Katalog Master Inventaris & Simulasi Prediksi
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::match(['get', 'post'], '/analytics/simulate/{productId}', [AnalyticsController::class, 'simulate'])->name('analytics.simulate');

    // Dasbor Utama & Pengaturan Profil
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 1. Modul Operasional Gudang: Mutasi Masuk & Keluar (Staf Gudang & Manajer Operasional)
    Route::middleware(['role:staf-gudang|manajer-operasional'])->prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Transactions/Index');
        })->name('index');
        Route::get('/inbound', function () {
            return Inertia::render('Transactions/InboundCreate');
        })->name('inbound.create');
        Route::get('/outbound', function () {
            return Inertia::render('Transactions/OutboundCreate');
        })->name('outbound.create');
    });

    // 2. Modul Analitik & Mesin Peramalan DES (Mode Referensi & Pengujian Terbuka untuk Seluruh Tim)
    Route::middleware(['role:manajer-operasional|komisaris|staf-gudang|auditor-internal'])->prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
    });

    // 3. Modul Alur Persetujuan Pengadaan (Restock Approval Workflow)
    Route::prefix('restock')->name('restock.')->group(function () {
        // Monitoring & Pengajuan Draf (Manajer Operasional & Komisaris)
        Route::middleware(['role:manajer-operasional|komisaris'])->group(function () {
            Route::get('/', function () {
                return Inertia::render('Restock/Index');
            })->name('index');
        });

        // Otorisasi Persetujuan Khusus (Komisaris Only)
        Route::middleware(['role:komisaris'])->group(function () {
            Route::get('/approval', function () {
                return Inertia::render('Restock/Approval');
            })->name('approval');
        });
    });

    // 4. Modul Jejak Audit & Investigasi Forensik (Auditor Internal & Komisaris)
    Route::middleware(['role:auditor-internal|komisaris'])->prefix('audit')->name('audit.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Audit/Index');
        })->name('index');
    });

    // 5. Modul Manajemen Pengguna & Aktivasi Akun (Manajer Operasional & Komisaris)
    Route::middleware(['role:manajer-operasional|komisaris'])->prefix('users')->name('users.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Users/Index');
        })->name('index');
    });
});

require __DIR__.'/auth.php';
