<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\StockTransactionDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InboundTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        if ($products->isEmpty()) {
            $this->command?->warn('Belum ada produk untuk membuat seeder transaksi Inbound.');
            return;
        }

        // Ambil user staf gudang atau admin sebagai pencatat
        $creator = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['staf-gudang', 'manajer-operasional']);
        })->first() ?? User::first();

        $suppliers = [
            'PT Mitra Distribusi Nusantara',
            'PT Sinar Surya Logistik',
            'Global Retail Importers Ltd',
            'CV Sumber Makmur Jaya',
            'Supplier Prima Perkasa',
            'PT Tri Star Logistik Utama',
            'PT Sentosa Niaga Mandiri',
        ];

        $notesList = [
            'Penerimaan stok berkala dari distributor utama.',
            'Restock pengadaan barang kuartal.',
            'Penerimaan pesanan restock darurat.',
            'Penerimaan konsinyasi pabrik.',
            'Kiriman batch tambahan antisipasi lonjakan permintaan.',
        ];

        // 1. Buat batch tanggal historis (2011) yang selaras dengan rentang retail Kaggle
        $historicalDates = [
            '2011-08-05 09:30:00',
            '2011-08-12 14:15:00',
            '2011-08-19 10:00:00',
            '2011-08-26 11:20:00',
            '2011-09-02 08:45:00',
            '2011-09-09 13:10:00',
            '2011-09-16 09:15:00',
            '2011-09-23 15:30:00',
            '2011-09-30 11:00:00',
            '2011-10-07 10:20:00',
            '2011-10-14 14:00:00',
            '2011-10-21 09:40:00',
            '2011-10-28 11:15:00',
            '2011-11-04 13:45:00',
            '2011-11-11 08:30:00',
            '2011-11-18 10:50:00',
            '2011-11-25 15:00:00',
            '2011-12-02 09:10:00',
            '2011-12-06 14:20:00',
        ];

        // 2. Buat batch tanggal terkini (Oktober 2026) termasuk hari ini agar KPI Card Hari Ini terisi
        $todayStr = Carbon::today()->format('Y-m-d');
        $recentDates = [
            Carbon::today()->subDays(6)->format('Y-m-d') . ' 10:15:00',
            Carbon::today()->subDays(4)->format('Y-m-d') . ' 13:30:00',
            Carbon::today()->subDays(2)->format('Y-m-d') . ' 09:45:00',
            Carbon::today()->subDays(1)->format('Y-m-d') . ' 14:00:00',
            $todayStr . ' 08:30:00', // Hari ini batch 1
            $todayStr . ' 13:45:00', // Hari ini batch 2
        ];

        $allBatchDates = array_merge($historicalDates, $recentDates);

        DB::transaction(function () use ($allBatchDates, $suppliers, $notesList, $products, $creator) {
            $createdCount = 0;

            foreach ($allBatchDates as $idx => $dateStr) {
                $cDate = Carbon::parse($dateStr);
                $refNo = 'TRX-IN-' . $cDate->format('Ymd') . '-' . strtoupper(Str::random(4));
                $supplier = $suppliers[$idx % count($suppliers)];
                $note = $notesList[$idx % count($notesList)];

                $transaction = StockTransaction::create([
                    'reference_no' => $refNo,
                    'type' => 'inbound',
                    'transaction_date' => $cDate,
                    'party_name' => $supplier,
                    'notes' => $note,
                    'created_by' => $creator?->id,
                    'created_at' => $cDate,
                    'updated_at' => $cDate,
                ]);

                // Pilih 2 sampai 4 produk secara bergantian untuk transaksi ini
                $itemCount = min(rand(2, 4), $products->count());
                $shuffledProducts = $products->shuffle()->take($itemCount);

                $totalQty = 0;
                $itemSummaries = [];

                foreach ($shuffledProducts as $prod) {
                    $qty = rand(40, 150);
                    $price = $prod->unit_price ?: 2.50;

                    StockTransactionDetail::create([
                        'stock_transaction_id' => $transaction->id,
                        'product_id' => $prod->id,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'notes' => 'Batch batching ' . $prod->sku,
                        'created_at' => $cDate,
                        'updated_at' => $cDate,
                    ]);

                    $totalQty += $qty;
                    $itemSummaries[] = [
                        'sku' => $prod->sku,
                        'name' => $prod->name,
                        'quantity' => $qty,
                        'unit' => $prod->unit,
                    ];
                }

                // Untuk transaksi recent (2026), buat catatan audit trail
                if ($cDate->year >= 2026) {
                    ActivityLog::create([
                        'log_name' => 'inventory',
                        'description' => "Mencatat penerimaan barang masuk (Inbound): Ref {$transaction->reference_no} ({$totalQty} unit)",
                        'subject_type' => StockTransaction::class,
                        'subject_id' => $transaction->id,
                        'causer_type' => $creator ? get_class($creator) : null,
                        'causer_id' => $creator?->id,
                        'event' => 'inbound',
                        'properties' => [
                            'reference_no' => $transaction->reference_no,
                            'party_name' => $transaction->party_name,
                            'total_quantity' => $totalQty,
                            'items_count' => count($itemSummaries),
                            'items' => $itemSummaries,
                            'ip' => '127.0.0.1',
                            'user_agent' => 'Seeder/Sipaling-Automated-Inbound',
                        ],
                        'created_at' => $cDate,
                        'updated_at' => $cDate,
                    ]);
                }

                $createdCount++;
            }

            $this->command?->info("Berhasil membuat {$createdCount} transaksi Inbound.");
        });
    }
}
