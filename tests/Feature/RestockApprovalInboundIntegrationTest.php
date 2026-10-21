<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\RestockRequestService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RestockApprovalInboundIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected RestockRequestService $service;

    protected User $manager;

    protected User $commissioner;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->service = app(RestockRequestService::class);
        $this->manager = User::where('email', 'manajer@sipaling.com')->first();
        $this->commissioner = User::where('email', 'komisaris@sipaling.com')->first();

        $category = Category::create([
            'name' => 'Elektronik & Retail',
            'slug' => 'elektronik-retail',
            'description' => 'Kategori elektronik.',
        ]);

        $this->product = Product::create([
            'sku' => 'MON-24-001',
            'name' => 'Monitor LED 24 Inch',
            'category_id' => $category->id,
            'unit' => 'unit',
            'unit_price' => 1500000,
            'current_stock' => 10,
            'minimum_stock' => 5,
        ]);
    }

    public function test_approving_restock_without_auto_generate_does_not_create_inbound_transaction(): void
    {
        $request = $this->service->createRequest([
            'total_estimated_cost' => 3000000,
        ], $this->manager);

        $this->service->approve(
            request: $request,
            reviewer: $this->commissioner,
            notes: 'Disetujui tanpa auto-inbound',
            autoGenerateInbound: false
        );

        $this->assertEquals('approved', $request->fresh()->status);
        $this->assertEquals(0, StockTransaction::count());
        $this->assertEquals(10, $this->product->fresh()->current_stock);
    }

    public function test_approving_restock_with_auto_generate_triggers_inbound_creation(): void
    {
        $request = $this->service->createRequest([
            'total_estimated_cost' => 7500000,
            'general_notes' => 'Pengadaan monitor retail',
        ], $this->manager);

        $items = [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'unit_price' => 1500000,
                'notes' => 'Item monitor restock',
            ],
        ];

        $approvedRequest = $this->service->approve(
            request: $request,
            reviewer: $this->commissioner,
            notes: 'Disetujui dan terbitkan draf transaksi masuk',
            autoGenerateInbound: true,
            inboundItems: $items
        );

        $this->assertEquals('approved', $approvedRequest->status);

        // Verifikasi transaksi masuk (Inbound) otomatis terbentuk
        $this->assertEquals(1, StockTransaction::where('type', 'inbound')->count());

        $transaction = StockTransaction::where('type', 'inbound')->first();
        $this->assertStringStartsWith('TRX-IN-', $transaction->reference_no);
        $this->assertStringContainsString($request->request_no, $transaction->party_name);
        $this->assertStringContainsString($request->request_no, $transaction->notes);
        $this->assertEquals($this->commissioner->id, $transaction->created_by);

        // Verifikasi rincian mutasi barang masuk
        $this->assertEquals(1, $transaction->details()->count());
        $detail = $transaction->details()->first();
        $this->assertEquals($this->product->id, $detail->product_id);
        $this->assertEquals(5, $detail->quantity);

        // Verifikasi stok produk otomatis bertambah (10 + 5 = 15)
        $this->assertEquals(15, $this->product->fresh()->current_stock);

        // Verifikasi jejak audit tercatat
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'restock',
            'event' => 'restock_approved',
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'inventory',
            'event' => 'inbound',
        ]);
    }

    public function test_generate_inbound_draft_method_directly(): void
    {
        $request = $this->service->createRequest([], $this->manager);
        $this->service->approve($request, $this->commissioner, 'Disetujui');

        $items = [
            [
                'product_id' => $this->product->id,
                'quantity' => 20,
                'unit_price' => 1500000,
            ],
        ];

        $transaction = $this->service->generateInboundDraft($request, $this->manager, $items);

        $this->assertInstanceOf(StockTransaction::class, $transaction);
        $this->assertEquals('inbound', $transaction->type);
        $this->assertEquals(30, $this->product->fresh()->current_stock); // 10 + 20
    }

    public function test_generate_inbound_draft_fails_if_request_not_approved(): void
    {
        $pendingRequest = $this->service->createRequest([], $this->manager);

        $items = [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
            ],
        ];

        $this->expectException(ValidationException::class);
        $this->service->generateInboundDraft($pendingRequest, $this->manager, $items);
    }
}
