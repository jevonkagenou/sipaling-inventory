<?php

namespace Tests\Unit;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProductRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Elektronik & Gadget',
            'slug' => 'elektronik-gadget',
            'description' => 'Kategori peralatan elektronik inventaris.',
        ]);
    }

    public function test_store_product_request_passes_with_valid_data(): void
    {
        $data = [
            'sku' => 'LAP-THINKPAD-01',
            'name' => 'ThinkPad T14 Gen 3',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 15000000.00,
            'current_stock' => 25,
            'minimum_stock' => 5,
            'description' => 'Laptop operasional staf manajemen.',
        ];

        $request = new StoreProductRequest;
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_store_product_request_prevents_duplicate_sku_in_database(): void
    {
        // 1. Buat produk awal di database
        Product::create([
            'sku' => 'DUP-SKU-999',
            'name' => 'Monitor Dell 24 Inch',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 2500000,
            'current_stock' => 10,
            'minimum_stock' => 2,
        ]);

        // 2. Coba kirim data baru dengan SKU yang sama
        $data = [
            'sku' => 'DUP-SKU-999',
            'name' => 'Monitor LG 24 Inch Lainnya',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'unit_price' => 2400000,
            'current_stock' => 5,
            'minimum_stock' => 2,
        ];

        $request = new StoreProductRequest;
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('sku'));
        $this->assertSame('Kode SKU sudah terdaftar dalam sistem inventaris.', $validator->errors()->first('sku'));
    }

    public function test_store_product_request_validates_numeric_and_non_negative_quantities(): void
    {
        $invalidData = [
            'sku' => 'SKU-NUM-TEST',
            'name' => 'Barang Uji',
            'category_id' => $this->category->id,
            'unit' => 'pcs',
            'unit_price' => -5000,          // Negatif
            'current_stock' => -10,         // Negatif
            'minimum_stock' => 'bukan-angka', // Non-numerik
        ];

        $request = new StoreProductRequest;
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('unit_price'));
        $this->assertTrue($validator->errors()->has('current_stock'));
        $this->assertTrue($validator->errors()->has('minimum_stock'));
    }

    public function test_update_product_request_allows_same_sku_for_the_same_product(): void
    {
        $product = Product::create([
            'sku' => 'SAME-SKU-123',
            'name' => 'Keyboard Mechanical',
            'category_id' => $this->category->id,
            'unit' => 'pcs',
            'unit_price' => 850000,
            'current_stock' => 15,
            'minimum_stock' => 3,
        ]);

        // Kirim request update dengan SKU yang sama milik produk ini sendiri
        $request = new UpdateProductRequest;
        $request->setRouteResolver(function () use ($product) {
            $route = new Route('PUT', '/inventory/{product}', []);
            $route->parameters = ['product' => $product];

            return $route;
        });

        $updateData = [
            'sku' => 'SAME-SKU-123',
            'name' => 'Keyboard Mechanical RGB Pro', // Hanya ubah nama
            'category_id' => $this->category->id,
            'unit' => 'pcs',
            'unit_price' => 900000,
            'current_stock' => 20,
            'minimum_stock' => 5,
        ];

        $validator = Validator::make($updateData, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes(), 'Memperbarui produk dengan SKU yang sama harus lolos.');
    }

    public function test_update_product_request_prevents_duplicate_sku_from_another_product(): void
    {
        $product1 = Product::create([
            'sku' => 'EXISTING-SKU-A',
            'name' => 'Mouse Wireless A',
            'category_id' => $this->category->id,
            'unit' => 'pcs',
            'unit_price' => 150000,
            'current_stock' => 50,
            'minimum_stock' => 10,
        ]);

        $product2 = Product::create([
            'sku' => 'EXISTING-SKU-B',
            'name' => 'Mouse Wireless B',
            'category_id' => $this->category->id,
            'unit' => 'pcs',
            'unit_price' => 175000,
            'current_stock' => 30,
            'minimum_stock' => 5,
        ]);

        // Coba perbarui product2 menggunakan SKU milik product1
        $request = new UpdateProductRequest;
        $request->setRouteResolver(function () use ($product2) {
            $route = new Route('PUT', '/inventory/{product}', []);
            $route->parameters = ['product' => $product2];

            return $route;
        });

        $updateData = [
            'sku' => 'EXISTING-SKU-A', // Duplikat dari product1!
            'name' => 'Mouse Wireless B Edit',
            'category_id' => $this->category->id,
            'unit' => 'pcs',
            'unit_price' => 175000,
            'current_stock' => 30,
            'minimum_stock' => 5,
        ];

        $validator = Validator::make($updateData, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('sku'));
        $this->assertSame('Kode SKU sudah terdaftar dalam sistem inventaris.', $validator->errors()->first('sku'));
    }
}
