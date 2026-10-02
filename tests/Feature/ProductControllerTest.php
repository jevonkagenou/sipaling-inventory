<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\StockTransactionDetail;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected User $staff;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(RoleAndPermissionSeeder::class);

        $this->manager = User::where('email', 'manajer@sipaling.com')->first();
        $this->staff = User::where('email', 'staf@sipaling.com')->first();

        $this->category = Category::create([
            'name' => 'Elektronik & Gadget',
            'slug' => 'elektronik-gadget',
            'description' => 'Kategori produk elektronik.',
        ]);
    }

    public function test_guest_cannot_access_inventory_or_products_endpoints(): void
    {
        $this->get('/inventory')->assertRedirect('/login');
        $this->get('/products')->assertRedirect('/login');
        $this->post('/inventory', [])->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_inventory_index(): void
    {
        Product::create([
            'sku' => 'LAP-T14-001',
            'name' => 'ThinkPad T14 Gen 3',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 15000000,
            'current_stock' => 10,
            'minimum_stock' => 2,
        ]);

        $response = $this->actingAs($this->staff)->get('/inventory');
        $response->assertOk();

        // Test JSON request
        $jsonResponse = $this->actingAs($this->staff)
            ->getJson('/inventory');

        $jsonResponse->assertOk()
            ->assertJsonFragment(['sku' => 'LAP-T14-001']);
    }

    public function test_non_manager_cannot_create_update_or_delete_products(): void
    {
        $product = Product::create([
            'sku' => 'PRD-TEST-001',
            'name' => 'Produk Tes',
            'category_id' => $this->category->id,
            'unit' => 'pcs',
            'unit_price' => 50000,
            'current_stock' => 20,
            'minimum_stock' => 5,
        ]);

        // Staf gudang tries to create
        $this->actingAs($this->staff)
            ->post('/inventory', [
                'sku' => 'PRD-FORBIDDEN',
                'name' => 'Produk Ilegal',
                'category_id' => $this->category->id,
                'unit' => 'pcs',
                'unit_price' => 10000,
                'current_stock' => 10,
                'minimum_stock' => 2,
            ])
            ->assertForbidden();

        // Staf gudang tries to update
        $this->actingAs($this->staff)
            ->put("/inventory/{$product->id}", [
                'sku' => 'PRD-TEST-001',
                'name' => 'Produk Tes Diubah',
                'category_id' => $this->category->id,
                'unit' => 'pcs',
                'unit_price' => 60000,
                'current_stock' => 25,
                'minimum_stock' => 5,
            ])
            ->assertForbidden();

        // Staf gudang tries to delete
        $this->actingAs($this->staff)
            ->delete("/inventory/{$product->id}")
            ->assertForbidden();
    }

    public function test_manager_can_create_product_and_it_records_activity_log(): void
    {
        $payload = [
            'sku' => 'MTR-DELL-24',
            'name' => 'Monitor Dell 24 Inch P2419H',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 2750000,
            'current_stock' => 15,
            'minimum_stock' => 3,
            'description' => 'Monitor IPS untuk tim operasional.',
        ];

        $response = $this->actingAs($this->manager)
            ->post('/inventory', $payload);

        $response->assertRedirect('/inventory');
        $response->assertSessionHas('success', 'Produk berhasil ditambahkan.');

        $this->assertDatabaseHas('products', [
            'sku' => 'MTR-DELL-24',
            'name' => 'Monitor Dell 24 Inch P2419H',
        ]);

        $createdProduct = Product::where('sku', 'MTR-DELL-24')->firstOrFail();

        // Verifikasi activity_log tercatat
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'inventory',
            'event' => 'created',
            'subject_type' => Product::class,
            'subject_id' => $createdProduct->id,
            'causer_id' => $this->manager->id,
        ]);

        $log = ActivityLog::where('subject_id', $createdProduct->id)->first();
        $this->assertStringContainsString('Menambahkan produk baru', $log->description);
    }

    public function test_manager_can_update_product_and_it_records_activity_log(): void
    {
        $product = Product::create([
            'sku' => 'KEY-LOGI-K120',
            'name' => 'Keyboard Logitech K120',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 120000,
            'current_stock' => 50,
            'minimum_stock' => 10,
        ]);

        $updatePayload = [
            'sku' => 'KEY-LOGI-K120', // SKU sama (valid)
            'name' => 'Keyboard Logitech K120 USB Original',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 135000,
            'current_stock' => 45,
            'minimum_stock' => 10,
            'description' => 'Diperbarui dengan deskripsi lengkap.',
        ];

        $response = $this->actingAs($this->manager)
            ->put("/inventory/{$product->id}", $updatePayload);

        $response->assertRedirect('/inventory');
        $response->assertSessionHas('success', 'Data produk berhasil diperbarui.');

        $product->refresh();
        $this->assertSame('Keyboard Logitech K120 USB Original', $product->name);
        $this->assertEquals(135000, $product->unit_price);

        // Verifikasi activity_log
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'inventory',
            'event' => 'updated',
            'subject_type' => Product::class,
            'subject_id' => $product->id,
            'causer_id' => $this->manager->id,
        ]);
    }

    public function test_destroy_is_restricted_when_product_has_stock_transactions(): void
    {
        $product = Product::create([
            'sku' => 'PRD-RESTRICT-01',
            'name' => 'Printer Epson L3210',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 2200000,
            'current_stock' => 5,
            'minimum_stock' => 1,
        ]);

        $transaction = StockTransaction::create([
            'reference_no' => 'TRX-IN-20261002-0001',
            'type' => 'inbound',
            'transaction_date' => now(),
            'party_name' => 'PT Supplier Utama',
            'created_by' => $this->manager->id,
        ]);

        StockTransactionDetail::create([
            'stock_transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 2200000,
        ]);

        // Coba hapus via Web request
        $response = $this->actingAs($this->manager)
            ->delete("/inventory/{$product->id}");

        $response->assertSessionHasErrors('error');
        $this->assertSame(
            'Produk tidak dapat dihapus karena sudah memiliki riwayat mutasi/transaksi inventaris.',
            session('errors')->first('error')
        );

        // Produk tetap ada di database
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);

        // Coba hapus via JSON request
        $jsonResponse = $this->actingAs($this->manager)
            ->deleteJson("/inventory/{$product->id}");

        $jsonResponse->assertStatus(422)
            ->assertJson([
                'message' => 'Produk tidak dapat dihapus karena sudah memiliki riwayat mutasi/transaksi inventaris.',
            ]);

        // Verifikasi tidak ada log 'deleted' untuk produk ini
        $this->assertDatabaseMissing('activity_log', [
            'subject_id' => $product->id,
            'event' => 'deleted',
        ]);
    }

    public function test_destroy_succeeds_when_product_has_no_transactions_and_records_activity_log(): void
    {
        $product = Product::create([
            'sku' => 'PRD-FREE-TO-DELETE',
            'name' => 'Barang Salah Input',
            'category_id' => $this->category->id,
            'unit' => 'pcs',
            'unit_price' => 10000,
            'current_stock' => 0,
            'minimum_stock' => 0,
        ]);

        $productId = $product->id;

        $response = $this->actingAs($this->manager)
            ->delete("/inventory/{$productId}");

        $response->assertRedirect('/inventory');
        $response->assertSessionHas('success', 'Produk berhasil dihapus.');

        // Pastikan terhapus dari database
        $this->assertDatabaseMissing('products', [
            'id' => $productId,
        ]);

        // Verifikasi activity_log tercatat
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'inventory',
            'event' => 'deleted',
            'subject_id' => $productId,
            'causer_id' => $this->manager->id,
        ]);
    }
}
