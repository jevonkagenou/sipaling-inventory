<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->manager = User::where('email', 'manajer@sipaling.com')->first();

        $this->category = Category::create([
            'name' => 'Elektronik & Retail',
            'slug' => 'elektronik-retail',
            'description' => 'Kategori elektronik ritel.',
        ]);
    }

    public function test_guest_cannot_access_export_restock(): void
    {
        $response = $this->get('/analytics/export-restock');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_manager_can_export_restock_excel_xlsx(): void
    {
        Product::create([
            'sku' => 'BRG-XLS-001',
            'name' => 'Monitor Gaming 27 Inch',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 3500000,
            'current_stock' => 1,
            'minimum_stock' => 5,
        ]);

        $response = $this->actingAs($this->manager)
            ->get('/analytics/export-restock?format=xlsx');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('.xlsx', $contentDisposition);

        $content = $response->streamedContent();
        $this->assertNotEmpty($content);
        // XLSX adalah file zip biner (dimulai dengan signature 'PK')
        $this->assertStringStartsWith("PK", $content);

        // Verifikasi pencatatan audit log
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'inventory',
            'event' => 'exported',
            'causer_id' => $this->manager->id,
        ]);
    }

    public function test_authenticated_manager_can_export_restock_csv(): void
    {
        Product::create([
            'sku' => 'BRG-CSV-001',
            'name' => 'Barang Uji CSV',
            'category_id' => $this->category->id,
            'unit' => 'pcs',
            'unit_price' => 50000,
            'current_stock' => 3,
            'minimum_stock' => 10,
        ]);

        $response = $this->actingAs($this->manager)
            ->get('/analytics/export-restock?format=csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('rekomendasi_restock_sipaling_', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);

        $content = $response->streamedContent();

        // Verifikasi UTF-8 BOM dan header kolom
        $this->assertStringContainsString('SKU Produk', $content);
        $this->assertStringContainsString('Nama Barang', $content);
        $this->assertStringContainsString('Stok Fisik Aktual', $content);
        $this->assertStringContainsString('Safety Stock (Minimum)', $content);
        $this->assertStringContainsString('Saran Kuantitas Restock', $content);
        $this->assertStringContainsString('Status Urgensi', $content);

        // Verifikasi produk masuk ke lembar kerja
        $this->assertStringContainsString('BRG-CSV-001', $content);
        $this->assertStringContainsString('Barang Uji CSV', $content);
    }
}
