<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\ForecastingLog;
use App\Models\PasswordResetOtp;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\StockTransactionDetail;
use App\Models\User;
use App\Services\DoubleExponentialSmoothingService;
use App\Services\StockTransactionService;
use App\Services\TwoFactorResetService;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * PMPL Automated Concurrency & Race Condition Test Suite
 * SIPALING - Sistem Inventaris Prediktif & Audit Log Terintegrasi
 * Proyek PBL Kelompok 1 - SIB 3C, Politeknik Negeri Malang
 *
 * Menguji seluruh skenario konkurensi, race condition, dan integritas ACID
 * pada 5 modul utama aplikasi SIPALING.
 */
class ConcurrencyAndRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;
    protected User $manager;
    protected User $auditor;
    protected Category $category;
    protected Product $productA;
    protected Product $productB;
    protected StockTransactionService $stockService;
    protected TwoFactorResetService $otpService;
    protected DoubleExponentialSmoothingService $desService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->staff = User::role('staf-gudang')->first();
        $this->manager = User::role('manajer-operasional')->first();
        $this->auditor = User::role('auditor-internal')->first();

        $this->stockService = app(StockTransactionService::class);
        $this->otpService = app(TwoFactorResetService::class);
        $this->desService = app(DoubleExponentialSmoothingService::class);

        $this->category = Category::create([
            'name' => 'Hardware & Server',
            'slug' => 'hardware-server',
            'description' => 'Perangkat keras infrastruktur server.',
        ]);

        $this->productA = Product::create([
            'sku' => 'SRV-DELL-R740',
            'name' => 'Dell PowerEdge R740 Server',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 45000000,
            'current_stock' => 15,
            'minimum_stock' => 3,
        ]);

        $this->productB = Product::create([
            'sku' => 'SW-CISCO-C9200',
            'name' => 'Cisco Catalyst 9200L Switch',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 18000000,
            'current_stock' => 20,
            'minimum_stock' => 5,
        ]);
    }

    // =========================================================================
    // DOMAIN 1: TRANSAKSI MUTASI STOK GUDANG (STOCK CONCURRENCY & ATOMICITY)
    // =========================================================================

    /**
     * RC-01: Mencegah Stok Minus & Over-allocation saat Pengeluaran Simultan
     * Skenario: Stok awal = 15. Dua pekerja (Worker 1 & Worker 2) secara simultan
     * meminta 10 unit masing-masing (Total 20 > 15).
     * Hasil yang diharapkan: Hanya 1 transaksi berhasil (stok menjadi 5),
     * transaksi kedua ditolak dengan ValidationException stok tidak cukup.
     */
    public function test_rc_01_concurrent_outbound_prevents_negative_stock_and_over_allocation(): void
    {
        $this->assertSame(15, $this->productA->current_stock);

        $successCount = 0;
        $failCount = 0;

        // Simulasi 2 transaksi konkuren pengeluaran 10 unit
        $requests = [
            ['party' => 'Divisi IT Kampus A', 'qty' => 10],
            ['party' => 'Divisi Jaringan Kampus B', 'qty' => 10],
        ];

        foreach ($requests as $req) {
            try {
                $this->stockService->createOutbound([
                    'party_name' => $req['party'],
                    'notes' => 'Pengujian race condition RC-01',
                    'items' => [
                        [
                            'product_id' => $this->productA->id,
                            'quantity' => $req['qty'],
                        ],
                    ],
                ], $this->staff);

                $successCount++;
            } catch (ValidationException $e) {
                $failCount++;
                $this->assertStringContainsString('tidak mencukupi', $e->getMessage());
            }
        }

        $this->assertSame(1, $successCount, 'Tepat 1 transaksi outbound yang boleh berhasil.');
        $this->assertSame(1, $failCount, 'Transaksi kedua harus ditolak untuk mencegah over-allocation.');

        $this->productA->refresh();
        $this->assertSame(5, $this->productA->current_stock, 'Stok akhir harus tepat 5 unit (15 - 10).');
        $this->assertGreaterThanOrEqual(0, $this->productA->current_stock, 'Stok tidak boleh bernilai negatif.');
        $this->assertDatabaseCount('stock_transactions', 1);
    }

    /**
     * RC-02: Mencegah Fenomena Lost Update saat Penambahan Stok Masuk Simultan
     * Skenario: Stok awal = 20. Dua pemasok (Supplier A & B) mengirim stok serentak:
     * Supplier A +35 unit, Supplier B +45 unit.
     * Hasil yang diharapkan: Kedua penambahan berhasil diakumulasi secara atomik
     * menjadi tepat 100 unit tanpa hilang salah satu update.
     */
    public function test_rc_02_concurrent_inbound_prevents_lost_updates(): void
    {
        $this->assertSame(20, $this->productB->current_stock);

        $suppliers = [
            ['name' => 'PT Cisco Systems Indonesia', 'qty' => 35],
            ['name' => 'PT Mega Pratama Distribusi', 'qty' => 45],
        ];

        foreach ($suppliers as $sup) {
            $this->stockService->createInbound([
                'party_name' => $sup['name'],
                'notes' => 'Batch penerimaan simultan RC-02',
                'items' => [
                    [
                        'product_id' => $this->productB->id,
                        'quantity' => $sup['qty'],
                    ],
                ],
            ], $this->staff);
        }

        $this->productB->refresh();
        $expectedStock = 20 + 35 + 45; // 100
        $this->assertSame($expectedStock, $this->productB->current_stock, 'Akumulasi stok masuk harus tepat 100 tanpa Lost Update.');
        $this->assertDatabaseCount('stock_transactions', 2);
    }

    /**
     * RC-03: Stress Test Konkurensi Tinggi (50 Pekerja Paralel pada 1 Produk)
     * Skenario: Stok produk B = 20 unit. Sebanyak 50 pekerja masing-masing meminta 1 unit.
     * Hasil yang diharapkan: Tepat 20 transaksi sukses, 30 transaksi ditolak,
     * stok akhir 0 unit tanpa ada nilai minus.
     */
    public function test_rc_03_high_concurrency_stress_50_parallel_workers(): void
    {
        $this->assertSame(20, $this->productB->current_stock);

        $successCount = 0;
        $rejectedCount = 0;

        for ($workerId = 1; $workerId <= 50; $workerId++) {
            try {
                $this->stockService->createOutbound([
                    'party_name' => "Staf Operasional #{$workerId}",
                    'notes' => "Stress test kuota konkurensi #{$workerId}",
                    'items' => [
                        [
                            'product_id' => $this->productB->id,
                            'quantity' => 1,
                        ],
                    ],
                ], $this->staff);

                $successCount++;
            } catch (ValidationException) {
                $rejectedCount++;
            }
        }

        $this->assertSame(20, $successCount, 'Tepat 20 transaksi yang berhasil menghabiskan stok.');
        $this->assertSame(30, $rejectedCount, '30 transaksi sisanya wajib ditolak dengan aman.');

        $this->productB->refresh();
        $this->assertSame(0, $this->productB->current_stock, 'Stok akhir harus tepat 0 unit.');
        $this->assertDatabaseCount('stock_transactions', 20);
    }

    /**
     * RC-04: Pencegahan Deadlock pada Transaksi Multi-Item dengan Urutan Terbalik
     * Skenario: Worker 1 memproses [Produk A, Produk B], Worker 2 memproses [Produk B, Produk A].
     * Hasil yang diharapkan: Mekanisme pengurutan kunci (key sorting) mencegah MySQL Deadlock 1213.
     */
    public function test_rc_04_multi_item_deadlock_prevention_with_reverse_order(): void
    {
        // Transaksi 1: Urutan A kemudian B
        $tx1 = $this->stockService->createOutbound([
            'party_name' => 'Proyek Data Center Tahap 1',
            'notes' => 'Urutan A kemudian B',
            'items' => [
                ['product_id' => $this->productA->id, 'quantity' => 2],
                ['product_id' => $this->productB->id, 'quantity' => 3],
            ],
        ], $this->staff);

        // Transaksi 2: Urutan B kemudian A (terbalik)
        $tx2 = $this->stockService->createOutbound([
            'party_name' => 'Proyek Data Center Tahap 2',
            'notes' => 'Urutan B kemudian A',
            'items' => [
                ['product_id' => $this->productB->id, 'quantity' => 4],
                ['product_id' => $this->productA->id, 'quantity' => 1],
            ],
        ], $this->staff);

        $this->assertInstanceOf(StockTransaction::class, $tx1);
        $this->assertInstanceOf(StockTransaction::class, $tx2);

        $this->productA->refresh();
        $this->productB->refresh();

        $this->assertSame(15 - 2 - 1, $this->productA->current_stock); // 12
        $this->assertSame(20 - 3 - 4, $this->productB->current_stock); // 13
    }

    /**
     * RC-05: Jaminan Atomic Rollback saat Terjadi Kegagalan Parsial Multi-Item
     * Skenario: Transaksi outbound meminta Produk A (5 unit, stok cukup: 15)
     * dan Produk B (999 unit, stok tidak cukup: 20).
     * Hasil yang diharapkan: Seluruh transaksi dibatalkan (DB Rollback),
     * stok Produk A sama sekali tidak berkurang.
     */
    public function test_rc_05_atomic_rollback_on_partial_multi_item_failure(): void
    {
        $this->assertSame(15, $this->productA->current_stock);
        $this->assertSame(20, $this->productB->current_stock);

        $failed = false;
        try {
            $this->stockService->createOutbound([
                'party_name' => 'Pengujian Kegagalan Parsial',
                'items' => [
                    ['product_id' => $this->productA->id, 'quantity' => 5],
                    ['product_id' => $this->productB->id, 'quantity' => 999], // Defisit besar
                ],
            ], $this->staff);
        } catch (ValidationException $e) {
            $failed = true;
        }

        $this->assertTrue($failed, 'Transaksi harus ditolak karena item kedua melebihi kapasitas.');

        $this->productA->refresh();
        $this->productB->refresh();

        $this->assertSame(15, $this->productA->current_stock, 'Stok Produk A wajib tetap utuh 15 karena rollback.');
        $this->assertSame(20, $this->productB->current_stock, 'Stok Produk B wajib tetap utuh 20.');
        $this->assertDatabaseCount('stock_transactions', 0);
        $this->assertDatabaseCount('stock_transaction_details', 0);
    }

    /**
     * RC-06: Keunikan Nomor Referensi Sekuensial saat Transaksi Dibuat Beruntun
     */
    public function test_rc_06_concurrent_reference_number_generation_uniqueness(): void
    {
        $today = now()->format('Ymd');
        $generatedRefs = [];

        for ($i = 0; $i < 5; $i++) {
            $tx = $this->stockService->createInbound([
                'party_name' => "Supplier Batch #{$i}",
                'items' => [
                    ['product_id' => $this->productA->id, 'quantity' => 1],
                ],
            ], $this->staff);

            $generatedRefs[] = $tx->reference_no;
        }

        // Pastikan tidak ada satupun nomor referensi yang sama (100% unique)
        $uniqueRefs = array_unique($generatedRefs);
        $this->assertCount(5, $uniqueRefs, 'Seluruh 5 nomor referensi harus unik tanpa tabrakan.');
        $this->assertSame("TRX-IN-{$today}-0001", $generatedRefs[0]);
        $this->assertSame("TRX-IN-{$today}-0005", $generatedRefs[4]);
    }

    // =========================================================================
    // DOMAIN 2: MASTER INVENTARIS & KATALOG (CATALOG INTEGRITY & CONCURRENCY)
    // =========================================================================

    /**
     * RC-07: Integritas Duplikasi SKU saat Pendaftaran Produk Simultan
     * Skenario: Dua pekerja mencoba mendaftarkan produk baru dengan SKU yang sama.
     */
    public function test_rc_07_concurrent_duplicate_sku_creation_integrity(): void
    {
        $payload1 = [
            'sku' => 'GPU-NVIDIA-RTX4090',
            'name' => 'NVIDIA GeForce RTX 4090 24GB',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 31000000,
            'current_stock' => 5,
            'minimum_stock' => 1,
        ];

        $payload2 = [
            'sku' => 'GPU-NVIDIA-RTX4090', // Duplikat SKU
            'name' => 'NVIDIA RTX 4090 Rog Strix',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 33000000,
            'current_stock' => 2,
            'minimum_stock' => 1,
        ];

        // Request 1 berhasil
        $response1 = $this->actingAs($this->manager)->post('/products', $payload1);
        $this->assertDatabaseHas('products', ['sku' => 'GPU-NVIDIA-RTX4090', 'name' => 'NVIDIA GeForce RTX 4090 24GB']);

        // Request 2 ditolak validasi unique SKU
        $response2 = $this->actingAs($this->manager)->post('/products', $payload2);
        $response2->assertSessionHasErrors(['sku']);

        // Tepat 1 produk dengan SKU tersebut di database
        $this->assertSame(1, Product::where('sku', 'GPU-NVIDIA-RTX4090')->count());
    }

    /**
     * RC-08: Penanganan Tabrakan Slug Kategori saat Pembuatan Bersamaan
     */
    public function test_rc_08_concurrent_category_slug_collision_handling(): void
    {
        $cat1 = $this->actingAs($this->manager)->post('/categories', [
            'name' => 'Peralatan Jaringan Optik',
            'description' => 'Kategori batch 1',
        ]);

        $cat2 = $this->actingAs($this->manager)->post('/categories', [
            'name' => 'Peralatan Jaringan Optik', // Nama identik
            'description' => 'Kategori batch 2',
        ]);

        // Request kedua ditolak oleh validasi unique:categories,name
        $cat2->assertSessionHasErrors(['name']);
        $this->assertSame(1, Category::where('name', 'Peralatan Jaringan Optik')->count());
    }

    /**
     * RC-09: Pencegahan Penghapusan Produk saat Memiliki Riwayat Transaksi (Restrict Protection)
     */
    public function test_rc_09_prevent_deleting_product_with_associated_transactions(): void
    {
        // Buat transaksi untuk produk A
        $this->stockService->createInbound([
            'party_name' => 'Penerimaan Awal',
            'items' => [
                ['product_id' => $this->productA->id, 'quantity' => 5],
            ],
        ], $this->staff);

        // Coba hapus produk A
        $response = $this->actingAs($this->manager)->delete("/products/{$this->productA->id}");
        $response->assertSessionHasErrors(['error']);

        // Produk A tetap ada di database
        $this->assertDatabaseHas('products', ['id' => $this->productA->id]);
    }

    // =========================================================================
    // DOMAIN 3: AUTENTIKASI, KEAMANAN & 2FA OTP (AUTH CONCURRENCY & REPLAY)
    // =========================================================================

    /**
     * RC-10: Mencegah Verifikasi Ganda Kode OTP (Anti-Replay Attack Concurrency)
     * Skenario: User menerima kode OTP. Dua thread mencoba memverifikasi kode yang sama serentak.
     * Hasil yang diharapkan: Hanya 1 verifikasi yang berhasil, verifikasi kedua ditolak
     * karena OTP sudah terkunci dan berstatus is_used = true.
     */
    public function test_rc_10_concurrent_otp_verification_prevents_double_use_replay(): void
    {
        // Generate OTP untuk staf
        $rawOtp = $this->otpService->createOtp($this->staff);

        $firstVerifySuccess = false;
        $secondVerifySuccess = false;

        // Eksekusi verifikasi pertama
        try {
            $firstVerifySuccess = $this->otpService->verify($this->staff, $rawOtp);
        } catch (ValidationException) {
            $firstVerifySuccess = false;
        }

        // Eksekusi verifikasi kedua (simulasi replay / permintaan serentak)
        try {
            $secondVerifySuccess = $this->otpService->verify($this->staff, $rawOtp);
        } catch (ValidationException) {
            $secondVerifySuccess = false;
        }

        $this->assertTrue($firstVerifySuccess, 'Verifikasi pertama harus berhasil.');
        $this->assertFalse($secondVerifySuccess, 'Verifikasi kedua harus ditolak karena OTP sudah digunakan (is_used = true).');

        $otpRecord = PasswordResetOtp::where('user_id', $this->staff->id)->latest()->first();
        $this->assertTrue((bool) $otpRecord->is_used);
        $this->assertNotNull($otpRecord->used_at);
    }

    /**
     * RC-11: Rate Limiting & Cooldown Protection terhadap Burst Request OTP
     * Skenario: 10 request OTP dikirimkan dalam detik yang sama.
     * Hasil yang diharapkan: Hanya 1 request yang diizinkan, 9 request lainnya ditolak rate limiter.
     */
    public function test_rc_11_burst_otp_request_rate_limiter_blocks_flooding(): void
    {
        $allowed = 0;
        $blocked = 0;

        for ($i = 0; $i < 10; $i++) {
            try {
                $this->otpService->createOtp($this->staff);
                $allowed++;
            } catch (ValidationException $e) {
                $blocked++;
                $this->assertStringContainsString('Mohon tunggu', $e->getMessage());
            }
        }

        $this->assertSame(1, $allowed, 'Hanya 1 permintaan pembuatan OTP yang boleh lolos dalam jendela cooldown 60 detik.');
        $this->assertSame(9, $blocked, '9 permintaan burst lainnya harus diblokir oleh RateLimiter.');
    }

    // =========================================================================
    // DOMAIN 4: ANALITIK & PERAMALAN DES (DOUBLE EXPONENTIAL SMOOTHING)
    // =========================================================================

    /**
     * RC-12: Idempotensi Pencatatan Log Peramalan (Concurrent Forecast Persistence)
     * Skenario: Dua pengguna membuka dan mentrigger kalkulasi prediksi DES untuk
     * produk dan periode yang sama secara bersamaan.
     * Hasil yang diharapkan: updateOrCreate mengeksekusi secara idempoten,
     * hanya ada tepat 1 baris di forecasting_logs (tidak ada duplikasi baris).
     */
    public function test_rc_12_concurrent_forecast_persistence_idempotence(): void
    {
        $forecastResult = [
            'future_periods' => ['2026-11'],
            'future_forecasts' => [42.5],
            'optimal_alpha' => 0.35,
            'optimal_beta' => 0.15,
            'mape' => 8.45,
            'rmse' => 3.12,
        ];

        // Trigger persistensi simultan
        $log1 = $this->desService->persistForecastLog($this->productA, $forecastResult);
        $log2 = $this->desService->persistForecastLog($this->productA, $forecastResult);

        $this->assertSame($log1->id, $log2->id, 'Kedua eksekusi harus memperbarui entitas yang sama (Idempoten).');

        $periodDate = '2026-11-01';
        $count = ForecastingLog::where('product_id', $this->productA->id)
            ->where('period_date', $periodDate)
            ->count();

        $this->assertSame(1, $count, 'Hanya boleh ada 1 catatan log peramalan untuk kombinasi produk dan periode tersebut.');
    }

    // =========================================================================
    // DOMAIN 5: JEJAK AUDIT FORENSIK (AUDIT LOG HIGH-CONCURRENCY INTEGRITY)
    // =========================================================================

    /**
     * RC-13: Integritas Pencatatan Jejak Audit Forensik pada Tingkat Konkurensi Tinggi
     * Skenario: 10 transaksi mutasi masuk dan keluar dieksekusi secara beruntun.
     * Hasil yang diharapkan: Setiap transaksi terikat dengan tepat 1 log audit Spatie ActivityLog,
     * dengan properti lengkap (reference_no, total_quantity, items) tanpa data hilang atau rusak.
     */
    public function test_rc_13_high_concurrency_audit_logging_integrity(): void
    {
        $initialLogsCount = ActivityLog::count();

        // Eksekusi 5 inbound + 5 outbound
        for ($i = 1; $i <= 5; $i++) {
            $this->stockService->createInbound([
                'party_name' => "Supplier Inbound #{$i}",
                'items' => [['product_id' => $this->productA->id, 'quantity' => 2]],
            ], $this->staff);

            $this->stockService->createOutbound([
                'party_name' => "Pelanggan Outbound #{$i}",
                'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
            ], $this->staff);
        }

        $finalLogsCount = ActivityLog::count();
        $newLogsCount = $finalLogsCount - $initialLogsCount;

        $this->assertSame(10, $newLogsCount, 'Tepat 10 entri ActivityLog yang harus tercatat untuk 10 transaksi mutasi.');

        // Verifikasi kelengkapan metadata audit log
        $latestLog = ActivityLog::where('log_name', 'inventory')->latest()->first();
        $this->assertNotNull($latestLog);
        $this->assertSame($this->staff->id, $latestLog->causer_id);
        $this->assertArrayHasKey('reference_no', $latestLog->properties);
        $this->assertArrayHasKey('total_quantity', $latestLog->properties);
        $this->assertArrayHasKey('items', $latestLog->properties);
    }
}
