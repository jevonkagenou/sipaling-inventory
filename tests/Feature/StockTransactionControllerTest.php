<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;

    protected User $manager;

    protected Category $category;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->staff = User::where('email', 'staf@sipaling.com')->first();
        $this->manager = User::where('email', 'manajer@sipaling.com')->first();

        $this->category = Category::create([
            'name' => 'Elektronik & Periferal',
            'slug' => 'elektronik-periferal',
            'description' => 'Kategori perangkat elektronik.',
        ]);

        $this->product = Product::create([
            'sku' => 'MON-SAMSUNG-27',
            'name' => 'Monitor Samsung 27 Curved',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 3200000,
            'current_stock' => 15,
            'minimum_stock' => 3,
        ]);
    }

    public function test_guest_cannot_access_transactions_routes(): void
    {
        $this->get('/transactions')->assertRedirect('/login');
        $this->get('/transactions/inbound')->assertRedirect('/login');
        $this->get('/transactions/outbound')->assertRedirect('/login');
    }

    public function test_staff_can_view_transactions_index_and_create_pages(): void
    {
        $this->actingAs($this->staff)->get('/transactions')->assertOk();
        $this->actingAs($this->staff)->get('/transactions/inbound')->assertOk();
        $this->actingAs($this->staff)->get('/transactions/outbound')->assertOk();
    }

    public function test_staff_can_store_inbound_transaction_and_increments_stock(): void
    {
        $payload = [
            'reference_no' => 'TRX-IN-TEST-001',
            'transaction_date' => now()->toDateString(),
            'party_name' => 'PT Samsung Electronics',
            'notes' => 'Penerimaan batch 1',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_price' => 3200000,
                ],
            ],
        ];

        $response = $this->actingAs($this->staff)->post('/transactions/inbound', $payload);

        $response->assertRedirect('/transactions');
        $response->assertSessionHas('success');

        $this->product->refresh();
        $this->assertSame(25, $this->product->current_stock); // 15 + 10

        $this->assertDatabaseHas('stock_transactions', [
            'reference_no' => 'TRX-IN-TEST-001',
            'type' => 'inbound',
        ]);
    }

    public function test_staff_can_store_outbound_transaction_and_decrements_stock(): void
    {
        $payload = [
            'reference_no' => 'TRX-OUT-TEST-001',
            'transaction_date' => now()->toDateString(),
            'party_name' => 'Divisi Desain Multimedia',
            'notes' => 'Pengadaan monitor divisi',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_price' => 3200000,
                ],
            ],
        ];

        $response = $this->actingAs($this->staff)->post('/transactions/outbound', $payload);

        $response->assertRedirect('/transactions');
        $response->assertSessionHas('success');

        $this->product->refresh();
        $this->assertSame(10, $this->product->current_stock); // 15 - 5

        $this->assertDatabaseHas('stock_transactions', [
            'reference_no' => 'TRX-OUT-TEST-001',
            'type' => 'outbound',
        ]);
    }

    public function test_outbound_store_fails_and_rolls_back_when_stock_is_insufficient(): void
    {
        $payload = [
            'reference_no' => 'TRX-OUT-EXCEED-001',
            'transaction_date' => now()->toDateString(),
            'party_name' => 'Divisi Finance',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 50, // Melebihi stok 15!
                ],
            ],
        ];

        $response = $this->actingAs($this->staff)->post('/transactions/outbound', $payload);

        $response->assertSessionHasErrors('items');

        // Stok tidak boleh berkurang
        $this->product->refresh();
        $this->assertSame(15, $this->product->current_stock);

        // Header transaksi tidak boleh tersimpan
        $this->assertDatabaseMissing('stock_transactions', [
            'reference_no' => 'TRX-OUT-EXCEED-001',
        ]);
    }
}
