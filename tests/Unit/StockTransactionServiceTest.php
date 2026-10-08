<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\StockTransactionService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class StockTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockTransactionService $service;

    protected User $user;

    protected Category $category;

    protected Product $productA;

    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new StockTransactionService;

        $this->user = User::factory()->create([
            'name' => 'Staf Logistik',
            'email' => 'logistik@sipaling.com',
        ]);

        $this->category = Category::create([
            'name' => 'Komputer & Aksesoris',
            'slug' => 'komputer-aksesoris',
            'description' => 'Perangkat komputer.',
        ]);

        $this->productA = Product::create([
            'sku' => 'LAP-DELL-01',
            'name' => 'Dell Latitude 5420',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 12000000,
            'current_stock' => 10,
            'minimum_stock' => 2,
        ]);

        $this->productB = Product::create([
            'sku' => 'MOU-LOGI-01',
            'name' => 'Logitech B100',
            'category_id' => $this->category->id,
            'unit' => 'pcs',
            'unit_price' => 75000,
            'current_stock' => 20,
            'minimum_stock' => 5,
        ]);
    }

    public function test_generate_reference_number_returns_standard_sequential_code(): void
    {
        $today = now()->format('Ymd');

        $inboundRef1 = $this->service->generateReferenceNumber('inbound');
        $this->assertSame("TRX-IN-{$today}-0001", $inboundRef1);

        // Simulasi transaksi tersimpan dengan nomor tersebut
        StockTransaction::create([
            'reference_no' => $inboundRef1,
            'type' => 'inbound',
            'transaction_date' => now(),
            'created_by' => $this->user->id,
        ]);

        // Nomor berikutnya harus sekuensial (0002)
        $inboundRef2 = $this->service->generateReferenceNumber('inbound');
        $this->assertSame("TRX-IN-{$today}-0002", $inboundRef2);

        // Uji untuk outbound
        $outboundRef = $this->service->generateReferenceNumber('outbound');
        $this->assertSame("TRX-OUT-{$today}-0001", $outboundRef);
    }

    public function test_generate_reference_number_throws_exception_on_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->generateReferenceNumber('transfer');
    }

    public function test_create_inbound_increases_product_stock_atomically(): void
    {
        $data = [
            'party_name' => 'PT Distributor Utama',
            'notes' => 'Penerimaan stok berkala',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 5,
                    'unit_price' => 12000000,
                ],
                [
                    'product_id' => $this->productB->id,
                    'quantity' => 15,
                    'unit_price' => 75000,
                ],
            ],
        ];

        $transaction = $this->service->createInbound($data, $this->user);

        $this->assertInstanceOf(StockTransaction::class, $transaction);
        $this->assertSame('inbound', $transaction->type);
        $this->assertSame('PT Distributor Utama', $transaction->party_name);
        $this->assertCount(2, $transaction->details);

        // Verifikasi penambahan stok produk
        $this->productA->refresh();
        $this->productB->refresh();
        $this->assertSame(15, $this->productA->current_stock); // 10 + 5
        $this->assertSame(35, $this->productB->current_stock); // 20 + 15

        $this->assertDatabaseHas('stock_transactions', [
            'id' => $transaction->id,
            'type' => 'inbound',
        ]);
        $this->assertDatabaseHas('stock_transaction_details', [
            'stock_transaction_id' => $transaction->id,
            'product_id' => $this->productA->id,
            'quantity' => 5,
        ]);
    }

    public function test_create_outbound_decreases_product_stock_atomically(): void
    {
        $data = [
            'party_name' => 'Divisi Marketing',
            'notes' => 'Pengeluaran untuk tim lapangan',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 4,
                    'unit_price' => 12000000,
                ],
                [
                    'product_id' => $this->productB->id,
                    'quantity' => 10,
                    'unit_price' => 75000,
                ],
            ],
        ];

        $transaction = $this->service->createOutbound($data, $this->user);

        $this->assertSame('outbound', $transaction->type);

        // Verifikasi pengurangan stok
        $this->productA->refresh();
        $this->productB->refresh();
        $this->assertSame(6, $this->productA->current_stock); // 10 - 4
        $this->assertSame(10, $this->productB->current_stock); // 20 - 10
    }

    public function test_create_outbound_rolls_back_automatically_on_insufficient_stock(): void
    {
        // Product A punya stok 10, Product B punya stok 20
        // Coba minta Product A sebanyak 5 (cukup), tapi Product B sebanyak 25 (melebihi stok 20!)
        $data = [
            'party_name' => 'Toko Cabang Surabaya',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 5,
                ],
                [
                    'product_id' => $this->productB->id,
                    'quantity' => 25, // MELEBIHI STOK (25 > 20)
                ],
            ],
        ];

        try {
            $this->service->createOutbound($data, $this->user);
            $this->fail('Harus melempar ValidationException karena stok tidak mencukupi.');
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->errors()['items']));
            $this->assertStringContainsString('tidak mencukupi', $e->errors()['items'][0]);
        }

        // VERIFIKASI TRANSAKSI DIBATALKAN (ROLLBACK) OTOMATIS:
        // 1. Stok produk A tidak boleh berkurang sama sekali (tetap 10)
        $this->productA->refresh();
        $this->assertSame(10, $this->productA->current_stock);

        // 2. Stok produk B tetap 20
        $this->productB->refresh();
        $this->assertSame(20, $this->productB->current_stock);

        // 3. Header transaksi tidak boleh tersimpan di database
        $this->assertDatabaseCount('stock_transactions', 0);

        // 4. Detail transaksi tidak boleh ada yang tersimpan di database
        $this->assertDatabaseCount('stock_transaction_details', 0);
    }

    public function test_transaction_rolls_back_automatically_on_unexpected_system_failure(): void
    {
        // Simulasikan kegagalan sistem di tengah proses (misal exception setelah item pertama)
        $data = [
            'party_name' => 'Vendor Pengujian',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 5,
                ],
                [
                    // ID produk yang sengaja dibuat memicu kegagalan / non-existent id
                    'product_id' => '00000000-0000-0000-0000-000000000000',
                    'quantity' => 3,
                ],
            ],
        ];

        try {
            $this->service->createInbound($data, $this->user);
            $this->fail('Harus melempar ModelNotFoundException karena produk kedua tidak ditemukan.');
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // VERIFIKASI ROLLBACK OTOMATIS:
        // Item A yang diproses pertama kali harus di-rollback (stok kembali ke 10, bukan 15)
        $this->productA->refresh();
        $this->assertSame(10, $this->productA->current_stock);

        // Tidak ada header transaksi dan detail yang tertinggal
        $this->assertDatabaseCount('stock_transactions', 0);
        $this->assertDatabaseCount('stock_transaction_details', 0);
    }
}
