<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected User $staff;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->manager = User::where('email', 'manajer@sipaling.com')->first();
        $this->staff = User::where('email', 'staf@sipaling.com')->first();

        $this->category = Category::create([
            'name' => 'Elektronik & Retail',
            'slug' => 'elektronik-retail',
            'description' => 'Kategori produk ritel elektronik.',
        ]);
    }

    /**
     * Skenario 1: Guest (Tamu belum login) dilarang mengakses seluruh endpoint kategori.
     */
    public function test_guest_cannot_access_category_endpoints(): void
    {
        $this->get('/categories')->assertRedirect('/login');
        $this->post('/categories', ['name' => 'Periferal'])->assertRedirect('/login');
        $this->put("/categories/{$this->category->id}", ['name' => 'Periferal'])->assertRedirect('/login');
        $this->delete("/categories/{$this->category->id}")->assertRedirect('/login');
    }

    /**
     * Skenario 2: Manajer Operasional berhasil melihat daftar dan menambah kategori baru.
     */
    public function test_manager_can_list_and_create_category(): void
    {
        // 1. Tinjau daftar kategori (GET)
        $response = $this->actingAs($this->manager)->get('/categories');
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonFragment(['name' => 'Elektronik & Retail']);

        // 2. Tambah kategori baru (POST)
        $createResponse = $this->actingAs($this->manager)->post('/categories', [
            'name' => 'Peralatan Jaringan',
            'description' => 'Kabel LAN, Router, dan Switch',
        ]);

        $createResponse->assertStatus(302);
        $createResponse->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Peralatan Jaringan',
            'slug' => 'peralatan-jaringan',
            'description' => 'Kabel LAN, Router, dan Switch',
        ]);

        // Verifikasi jejak audit log tercatat
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'inventory',
            'event' => 'created',
            'causer_id' => $this->manager->id,
        ]);
    }

    /**
     * Skenario 3: Pengguna tanpa wewenang kelola (Staf Gudang) ditolak 403 Forbidden.
     */
    public function test_staff_cannot_create_or_modify_category(): void
    {
        // Staf Gudang boleh melihat katalog (GET), namun DILARANG menambah/mengubah/menghapus
        $this->actingAs($this->staff)->get('/categories')->assertStatus(200);

        $this->actingAs($this->staff)->post('/categories', [
            'name' => 'Kategori Ilegal',
        ])->assertStatus(403);

        $this->actingAs($this->staff)->put("/categories/{$this->category->id}", [
            'name' => 'Ubah Ilegal',
        ])->assertStatus(403);

        $this->actingAs($this->staff)->delete("/categories/{$this->category->id}")->assertStatus(403);
    }

    /**
     * Skenario 4: Validasi error jika nama kategori kosong atau duplikat.
     */
    public function test_create_category_validation_fails_on_empty_and_duplicate_name(): void
    {
        // Kasus 1: Nama kosong (required)
        $responseEmpty = $this->actingAs($this->manager)->post('/categories', [
            'name' => '',
            'description' => 'Tanpa nama',
        ]);
        $responseEmpty->assertSessionHasErrors(['name']);

        // Kasus 2: Nama duplikat (unique)
        $responseDuplicate = $this->actingAs($this->manager)->post('/categories', [
            'name' => 'Elektronik & Retail', // Sudah ada di setUp
            'description' => 'Duplikat',
        ]);
        $responseDuplicate->assertSessionHasErrors(['name']);
    }

    /**
     * Skenario 5: Manajer dapat memperbarui kategori dan slug diperbarui otomatis.
     */
    public function test_manager_can_update_category_and_slug(): void
    {
        $response = $this->actingAs($this->manager)->put("/categories/{$this->category->id}", [
            'name' => 'Elektronik Konsumen Modern',
            'description' => 'Deskripsi diperbarui',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'id' => $this->category->id,
            'name' => 'Elektronik Konsumen Modern',
            'slug' => 'elektronik-konsumen-modern',
            'description' => 'Deskripsi diperbarui',
        ]);

        // Verifikasi update dengan nama yang sama (self-ignore unique rule)
        $responseSame = $this->actingAs($this->manager)->put("/categories/{$this->category->id}", [
            'name' => 'Elektronik Konsumen Modern',
            'description' => 'Deskripsi diperbarui lagi',
        ]);
        $responseSame->assertSessionHasNoErrors();

        // Verifikasi audit log tercatat
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'inventory',
            'event' => 'updated',
            'causer_id' => $this->manager->id,
        ]);
    }

    /**
     * Skenario 6: Proteksi hapus — Kategori dilarang dihapus jika masih ada produk di dalamnya.
     */
    public function test_cannot_delete_category_with_associated_products(): void
    {
        // Hubungkan 1 produk ke kategori ini
        Product::create([
            'sku' => 'MON-TST-001',
            'name' => 'Monitor Uji 24 Inch',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 1200000,
            'current_stock' => 15,
            'minimum_stock' => 5,
        ]);

        $this->assertEquals(1, $this->category->products()->count());

        // Coba hapus kategori
        $response = $this->actingAs($this->manager)->delete("/categories/{$this->category->id}");

        $response->assertStatus(302);
        $response->assertSessionHasErrors('category_error');

        // Pastikan kategori MASIH ADA di database (tidak terhapus)
        $this->assertDatabaseHas('categories', [
            'id' => $this->category->id,
            'name' => 'Elektronik & Retail',
        ]);
    }

    /**
     * Skenario 7: Kategori kosong (tanpa produk) berhasil dihapus dari sistem.
     */
    public function test_can_delete_empty_category(): void
    {
        $emptyCategory = Category::create([
            'name' => 'Kategori Sementara',
            'slug' => 'kategori-sementara',
            'description' => 'Kategori kosong siap hapus',
        ]);

        $response = $this->actingAs($this->manager)->delete("/categories/{$emptyCategory->id}");

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Pastikan kategori telah terhapus dari database
        $this->assertDatabaseMissing('categories', [
            'id' => $emptyCategory->id,
        ]);

        // Verifikasi jejak audit log deleted tercatat
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'inventory',
            'event' => 'deleted',
            'causer_id' => $this->manager->id,
        ]);
    }
}
