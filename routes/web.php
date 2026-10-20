<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockTransactionController;
use App\Http\Controllers\UserController;
use App\Models\ActivityLog;
use App\Models\ForecastingLog;
use Illuminate\Http\Request;
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
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::match(['get', 'post'], '/analytics/simulate/{productId}', [AnalyticsController::class, 'simulate'])->name('analytics.simulate');

    // Operasi CRUD Master Produk & Kategori (Khusus Manajer Operasional & Komisaris)
    Route::middleware(['role_or_permission:manajer-operasional|komisaris|inventory.manage'])->group(function () {
        Route::get('/inventory/import-template', [ProductController::class, 'downloadTemplate'])->name('inventory.import-template');
        Route::post('/inventory/import', [ProductController::class, 'importCsv'])->name('inventory.import');

        Route::post('/inventory', [ProductController::class, 'store'])->name('inventory.store');
        Route::put('/inventory/{product}', [ProductController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{product}', [ProductController::class, 'destroy'])->name('inventory.destroy');

        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    // Dasbor Utama & Pengaturan Profil
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 1. Modul Operasional Gudang: Mutasi Masuk & Keluar
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::middleware(['role_or_permission:staf-gudang|manajer-operasional|komisaris|auditor-internal|transactions.view'])->group(function () {
            Route::get('/', [StockTransactionController::class, 'index'])->name('index');
            Route::get('/batch-receipt', [StockTransactionController::class, 'batchReceiptPdf'])->name('batch-receipt');
            Route::get('/{transaction}/receipt', [StockTransactionController::class, 'receiptPdf'])->name('receipt');
        });

        Route::middleware(['role_or_permission:staf-gudang|manajer-operasional|transactions.create'])->group(function () {
            Route::get('/inbound', [StockTransactionController::class, 'inboundCreate'])->name('inbound.create');
            Route::post('/inbound', [StockTransactionController::class, 'inboundStore'])->name('inbound.store');
            Route::get('/outbound', [StockTransactionController::class, 'outboundCreate'])->name('outbound.create');
            Route::post('/outbound', [StockTransactionController::class, 'outboundStore'])->name('outbound.store');
        });
    });

    // 2. Modul Analitik & Mesin Peramalan DES (Mode Referensi & Pengujian Terbuka untuk Seluruh Tim)
    Route::middleware(['role:manajer-operasional|komisaris|staf-gudang|auditor-internal'])->prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/export-restock', [AnalyticsController::class, 'exportRestock'])->name('export-restock');
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
        Route::get('/', function (Request $request) {
            $query = ActivityLog::with('causer:id,name,email');

            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('event', 'like', "%{$search}%")
                      ->orWhere('log_name', 'like', "%{$search}%")
                      ->orWhereHas('causer', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('event') && $request->input('event') !== 'all') {
                $query->where('event', $request->input('event'));
            }

            if ($request->filled('log_name') && $request->input('log_name') !== 'all') {
                $query->where('log_name', $request->input('log_name'));
            }

            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->input('start_date'));
            }

            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->input('end_date'));
            }

            $logs = $query->latest()->paginate(15)->withQueryString();

            $stats = [
                'total_logs' => ActivityLog::count(),
                'inventory_logs' => ActivityLog::where('log_name', 'inventory')->count(),
                'user_logs' => ActivityLog::where('log_name', 'users')->count(),
                'forecast_logs' => ForecastingLog::count(),
            ];

            return Inertia::render('Audit/Index', [
                'logs' => $logs,
                'stats' => $stats,
                'filters' => [
                    'search' => $request->input('search', ''),
                    'event' => $request->input('event', ''),
                    'log_name' => $request->input('log_name', ''),
                    'start_date' => $request->input('start_date', ''),
                    'end_date' => $request->input('end_date', ''),
                ],
            ]);
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

if (app()->environment('local')) {
    Route::get('/pmpl-report', function () {
        $path = base_path('cypress/reports/laporan_pengujian_pmpl.html');
        if (file_exists($path)) {
            return response()->file($path, ['Content-Type' => 'text/html; charset=UTF-8']);
        }
        abort(404, 'Laporan PMPL belum digenerate.');
    })->name('pmpl.report');
}
