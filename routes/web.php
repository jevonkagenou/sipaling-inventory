<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
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
    Route::get('/inventory', [ProductController::class, 'index'])->name('inventory.index');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::match(['get', 'post'], '/analytics/simulate/{productId}', [AnalyticsController::class, 'simulate'])->name('analytics.simulate');

    // Operasi CRUD Master Produk (Khusus Manajer Operasional)
    Route::middleware(['role_or_permission:manajer-operasional|inventory.manage'])->group(function () {
        Route::post('/inventory', [ProductController::class, 'store'])->name('inventory.store');
        Route::put('/inventory/{product}', [ProductController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{product}', [ProductController::class, 'destroy'])->name('inventory.destroy');

        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    // Dasbor Utama & Pengaturan Profil
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 1. Modul Operasional Gudang: Mutasi Masuk & Keluar (Staf Gudang & Manajer Operasional)
    Route::middleware(['role:staf-gudang|manajer-operasional'])->prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\StockTransactionController::class, 'index'])->name('index');
        Route::get('/inbound', [\App\Http\Controllers\StockTransactionController::class, 'inboundCreate'])->name('inbound.create');
        Route::post('/inbound', [\App\Http\Controllers\StockTransactionController::class, 'inboundStore'])->name('inbound.store');
        Route::get('/outbound', [\App\Http\Controllers\StockTransactionController::class, 'outboundCreate'])->name('outbound.create');
        Route::post('/outbound', [\App\Http\Controllers\StockTransactionController::class, 'outboundStore'])->name('outbound.store');
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
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/auth.php';
