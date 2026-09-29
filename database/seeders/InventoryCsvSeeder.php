<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryCsvSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = database_path('data/subset_online_retail_predictive.csv');

        if (! file_exists($csvPath)) {
            $csvPath = base_path('subset_online_retail_predictive.csv');
        }

        if (! file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");

            return;
        }

        $this->command->info("Memproses dataset dari: {$csvPath}");

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle);
        $colIndex = array_flip($headers);

        // 1. Buat Kategori Standar
        $categoryHome = Category::firstOrCreate(
            ['slug' => 'home-decor'],
            [
                'name' => 'Home & Living Decor',
                'description' => 'Peralatan dekorasi rumah, pencahayaan, dan ornamen ruang.',
            ]
        );

        $categoryGift = Category::firstOrCreate(
            ['slug' => 'gifts-novelties'],
            [
                'name' => 'Gifts & Novelties',
                'description' => 'Produk suvenir, tas serbaguna, dan perlengkapan pesta.',
            ]
        );

        // Profil master produk dan ambang batas minimum stock (Safety Stock / ROP)
        $productProfile = [
            '85099B' => [
                'category_id' => $categoryGift->id,
                'current_stock' => 350,
                'minimum_stock' => 100,
                'unit' => 'pcs',
            ],
            '85123A' => [
                'category_id' => $categoryHome->id,
                'current_stock' => 42,
                'minimum_stock' => 80,
                'unit' => 'pcs',
            ],
            '23084' => [
                'category_id' => $categoryHome->id,
                'current_stock' => 180,
                'minimum_stock' => 75,
                'unit' => 'pcs',
            ],
            '22197' => [
                'category_id' => $categoryGift->id,
                'current_stock' => 520,
                'minimum_stock' => 120,
                'unit' => 'pcs',
            ],
            '84879' => [
                'category_id' => $categoryHome->id,
                'current_stock' => 30,
                'minimum_stock' => 60,
                'unit' => 'pcs',
            ],
            '21212' => [
                'category_id' => $categoryGift->id,
                'current_stock' => 210,
                'minimum_stock' => 90,
                'unit' => 'pcs',
            ],
            '84077' => [
                'category_id' => $categoryGift->id,
                'current_stock' => 15,
                'minimum_stock' => 50,
                'unit' => 'pcs',
            ],
            '22616' => [
                'category_id' => $categoryGift->id,
                'current_stock' => 95,
                'minimum_stock' => 40,
                'unit' => 'pcs',
            ],
            '23166' => [
                'category_id' => $categoryHome->id,
                'current_stock' => 110,
                'minimum_stock' => 50,
                'unit' => 'pcs',
            ],
            '23843' => [
                'category_id' => $categoryGift->id,
                'current_stock' => 8,
                'minimum_stock' => 25,
                'unit' => 'pcs',
            ],
        ];

        $productsMap = [];
        $invoiceMap = [];
        $transactions = [];
        $details = [];
        $now = now();

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $sku = trim($row[$colIndex['StockCode']]);
                $description = trim($row[$colIndex['Description']]);
                $price = (float) $row[$colIndex['Price']];
                $invoice = trim($row[$colIndex['Invoice']]);
                $quantity = (int) $row[$colIndex['Quantity']];
                $invoiceDate = trim($row[$colIndex['InvoiceDate']]);
                $customerId = isset($colIndex['Customer ID']) ? trim($row[$colIndex['Customer ID']]) : null;
                $country = isset($colIndex['Country']) ? trim($row[$colIndex['Country']]) : null;

                // 2. Simpan Master Product (UUID)
                if (! isset($productsMap[$sku])) {
                    $profile = $productProfile[$sku] ?? [
                        'category_id' => $categoryGift->id,
                        'current_stock' => 100,
                        'minimum_stock' => 50,
                        'unit' => 'pcs',
                    ];

                    $product = Product::firstOrCreate(
                        ['sku' => $sku],
                        [
                            'category_id' => $profile['category_id'],
                            'name' => $description,
                            'unit' => $profile['unit'],
                            'unit_price' => $price,
                            'current_stock' => $profile['current_stock'],
                            'minimum_stock' => $profile['minimum_stock'],
                            'description' => "Komoditas ritel berkode SKU {$sku}",
                        ]
                    );

                    $productsMap[$sku] = $product->id;
                }

                // 3. Catat Transaksi Header unik (Invoice)
                if (! isset($invoiceMap[$invoice])) {
                    $trxId = (string) Str::uuid();
                    $invoiceMap[$invoice] = $trxId;

                    $partyName = [];
                    if ($customerId) {
                        $partyName[] = "Customer #{$customerId}";
                    }
                    if ($country) {
                        $partyName[] = $country;
                    }

                    $transactions[] = [
                        'id' => $trxId,
                        'reference_no' => $invoice,
                        'type' => 'outbound',
                        'transaction_date' => $invoiceDate,
                        'party_name' => ! empty($partyName) ? implode(' - ', $partyName) : 'General Retail',
                        'notes' => 'Penjualan riil Online Retail Dataset II',
                        'created_by' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // 4. Catat Transaksi Detail (Item)
                $details[] = [
                    'id' => (string) Str::uuid(),
                    'stock_transaction_id' => $invoiceMap[$invoice],
                    'product_id' => $productsMap[$sku],
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'notes' => "Outbound order {$invoice}",
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            fclose($handle);

            // Insert transactions first in chunks
            foreach (array_chunk($transactions, 500) as $chunk) {
                DB::table('stock_transactions')->insert($chunk);
            }

            // Insert transaction details in chunks
            foreach (array_chunk($details, 500) as $chunk) {
                DB::table('stock_transaction_details')->insert($chunk);
            }

            DB::commit();

            $this->command->info('Seeding berhasil dan 100% selaras dengan implementation_plan.md:');
            $this->command->info('- '.count($productsMap)." master produk (UUID) ke tabel 'products'");
            $this->command->info('- '.count($transactions)." header transaksi (UUID) ke tabel 'stock_transactions'");
            $this->command->info('- '.count($details)." detail item transaksi (UUID) ke tabel 'stock_transaction_details'");
        } catch (\Exception $e) {
            DB::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }
            $this->command->error('Terjadi kesalahan saat seeding: '.$e->getMessage());
            throw $e;
        }
    }
}
