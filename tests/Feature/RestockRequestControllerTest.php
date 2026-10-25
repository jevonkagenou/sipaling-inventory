<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\RestockRequestDetail;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\RestockRequestService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RestockRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $commissioner;
    protected User $warehouseStaff;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->manager = User::where('email', 'manajer@sipaling.com')->first();
        $this->commissioner = User::where('email', 'komisaris@sipaling.com')->first();
        $this->warehouseStaff = User::where('email', 'staf@sipaling.com')->first();

        $category = Category::create([
            'name' => 'Kategori Pangan & Sembako',
            'slug' => 'pangan-sembako',
            'description' => 'Bahan pokok kebutuhan pokok.',
        ]);

        $this->productA = Product::create([
            'sku' => 'PRD-MKO-001',
            'name' => 'Minyak Goreng Sawit 2L',
            'category_id' => $category->id,
            'unit' => 'Pouch',
            'unit_price' => 35000,
            'current_stock' => 10,
            'minimum_stock' => 30,
        ]);

        $this->productB = Product::create([
            'sku' => 'PRD-BRS-002',
            'name' => 'Beras Premium 5kg',
            'category_id' => $category->id,
            'unit' => 'Sak',
            'unit_price' => 70000,
            'current_stock' => 5,
            'minimum_stock' => 25,
        ]);
    }

    public function test_manager_can_view_restock_monitoring_page(): void
    {
        $response = $this->actingAs($this->manager)->get(route('restock.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Restock/Index')
            ->has('requests')
            ->has('filters')
            ->has('stats')
        );
    }

    public function test_commissioner_can_view_approval_queue_page(): void
    {
        $response = $this->actingAs($this->commissioner)->get(route('restock.approval'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Restock/Approval')
            ->has('requests')
        );
    }

    public function test_warehouse_staff_is_forbidden_from_restock_routes(): void
    {
        $this->actingAs($this->warehouseStaff)
            ->get(route('restock.index'))
            ->assertForbidden();

        $this->actingAs($this->warehouseStaff)
            ->get(route('restock.approval'))
            ->assertForbidden();
    }

    public function test_manager_can_store_multi_item_restock_request(): void
    {
        $payload = [
            'general_notes' => 'Pengadaan stok kritis minyak dan beras menghadapi lonjakan permintaan.',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'current_stock' => 10,
                    'safety_stock' => 30,
                    'suggested_quantity' => 100,
                    'requested_quantity' => 100,
                    'estimated_unit_price' => 35000,
                    'notes' => 'Merek Sania / Bimoli',
                ],
                [
                    'product_id' => $this->productB->id,
                    'current_stock' => 5,
                    'safety_stock' => 25,
                    'suggested_quantity' => 50,
                    'requested_quantity' => 50,
                    'estimated_unit_price' => 70000,
                    'notes' => 'Kualitas super',
                ],
            ],
        ];

        $response = $this->actingAs($this->manager)->post(route('restock.store'), $payload);

        $response->assertRedirect(route('restock.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('restock_requests', [
            'requested_by' => $this->manager->id,
            'status' => 'pending',
            'total_estimated_cost' => 7000000, // (100 * 35000) + (50 * 70000) = 3500000 + 3500000 = 7000000
        ]);

        $request = RestockRequest::first();
        $this->assertCount(2, $request->details);

        $this->assertDatabaseHas('restock_request_details', [
            'restock_request_id' => $request->id,
            'product_id' => $this->productA->id,
            'requested_quantity' => 100,
            'subtotal' => 3500000,
        ]);

        $this->assertDatabaseHas('restock_request_details', [
            'restock_request_id' => $request->id,
            'product_id' => $this->productB->id,
            'requested_quantity' => 50,
            'subtotal' => 3500000,
        ]);
    }

    public function test_store_validation_fails_when_items_are_empty_or_invalid(): void
    {
        $response = $this->actingAs($this->manager)->post(route('restock.store'), [
            'general_notes' => 'Catatan tanpa item',
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_commissioner_can_approve_restock_and_auto_generates_inbound_transaction(): void
    {
        $service = app(RestockRequestService::class);
        $request = $service->createRequest([
            'general_notes' => 'Pengadaan reguler kuartal IV',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'requested_quantity' => 40,
                    'estimated_unit_price' => 35000,
                ],
                [
                    'product_id' => $this->productB->id,
                    'requested_quantity' => 20,
                    'estimated_unit_price' => 70000,
                ],
            ],
        ], $this->manager);

        $this->assertEquals(10, $this->productA->fresh()->current_stock);
        $this->assertEquals(5, $this->productB->fresh()->current_stock);

        $response = $this->actingAs($this->commissioner)->post(route('restock.approve', $request->id), [
            'review_notes' => 'Disetujui penuh oleh Komisaris.',
        ]);

        $response->assertSessionHas('success');

        $freshRequest = $request->fresh();
        $this->assertEquals('approved', $freshRequest->status);
        $this->assertEquals($this->commissioner->id, $freshRequest->reviewed_by);
        $this->assertNotNull($freshRequest->reviewed_at);

        // Verifikasi detail approved_quantity disinkronkan
        foreach ($freshRequest->details as $detail) {
            $this->assertEquals($detail->requested_quantity, $detail->approved_quantity);
        }

        // Verifikasi transaksi Inbound otomatis terbit
        $inboundTrx = StockTransaction::where('type', 'inbound')->first();
        $this->assertNotNull($inboundTrx);
        $this->assertStringContainsString($request->request_no, $inboundTrx->party_name);
        $this->assertCount(2, $inboundTrx->details);

        // Verifikasi stok produk bertambah secara atomik
        $this->assertEquals(10 + 40, $this->productA->fresh()->current_stock);
        $this->assertEquals(5 + 20, $this->productB->fresh()->current_stock);
    }

    public function test_commissioner_can_reject_restock_with_mandatory_notes(): void
    {
        $service = app(RestockRequestService::class);
        $request = $service->createRequest([
            'general_notes' => 'Pengadaan pelengkap',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'requested_quantity' => 30,
                    'estimated_unit_price' => 35000,
                ],
            ],
        ], $this->manager);

        // Uji validasi catatan penolakan wajib diisi
        $emptyNotesResponse = $this->actingAs($this->commissioner)->post(route('restock.reject', $request->id), [
            'review_notes' => '',
        ]);
        $emptyNotesResponse->assertSessionHasErrors('review_notes');

        // Uji penolakan dengan catatan valid
        $response = $this->actingAs($this->commissioner)->post(route('restock.reject', $request->id), [
            'review_notes' => 'Harga estimasi terlalu tinggi, mohon ajukan penawaran vendor alternatif.',
        ]);

        $response->assertSessionHas('success');

        $freshRequest = $request->fresh();
        $this->assertEquals('rejected', $freshRequest->status);
        $this->assertEquals('Harga estimasi terlalu tinggi, mohon ajukan penawaran vendor alternatif.', $freshRequest->review_notes);

        // Detail approved_quantity diubah menjadi 0
        $this->assertEquals(0, $freshRequest->details->first()->approved_quantity);

        // Pastikan tidak ada transaksi inbound yang diterbitkan
        $this->assertEquals(0, StockTransaction::count());
        $this->assertEquals(10, $this->productA->fresh()->current_stock);
    }

    public function test_non_commissioner_cannot_approve_or_reject_restock(): void
    {
        $service = app(RestockRequestService::class);
        $request = $service->createRequest([
            'general_notes' => 'Pengadaan darurat',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'requested_quantity' => 10,
                ],
            ],
        ], $this->manager);

        // Manajer tidak boleh menyetujui
        $this->actingAs($this->manager)
            ->post(route('restock.approve', $request->id))
            ->assertForbidden();

        // Staf gudang tidak boleh menolak
        $this->actingAs($this->warehouseStaff)
            ->post(route('restock.reject', $request->id), ['review_notes' => 'Catatan'])
            ->assertForbidden();
    }

    public function test_concurrent_or_subsequent_approval_is_blocked_by_pessimistic_lock_and_anti_bypass(): void
    {
        $service = app(RestockRequestService::class);
        $request = $service->createRequest([
            'general_notes' => 'Pengadaan stok',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'requested_quantity' => 15,
                ],
            ],
        ], $this->manager);

        // Approval pertama sukses
        $this->actingAs($this->commissioner)
            ->post(route('restock.approve', $request->id))
            ->assertSessionHas('success');

        // Approval kedua (misal double-click atau concurrent tab) ditolak oleh Policy & Anti-Bypass
        $this->actingAs($this->commissioner)
            ->post(route('restock.approve', $request->id))
            ->assertForbidden();

        // Verifikasi transaksi Inbound hanya diterbitkan tepat 1 kali
        $this->assertEquals(1, StockTransaction::where('type', 'inbound')->count());
        $this->assertEquals(10 + 15, $this->productA->fresh()->current_stock);
    }
}
