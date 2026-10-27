<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\RestockRequestDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RestockRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        if ($products->isEmpty()) {
            $this->command?->warn('Belum ada data produk untuk membuat seeder RestockRequest.');

            return;
        }

        // Ambil akun pengguna berdasarkan role Spatie
        $manager = User::whereHas('roles', fn ($q) => $q->where('name', 'manajer-operasional'))->first()
            ?? User::first();

        $commissioner = User::whereHas('roles', fn ($q) => $q->where('name', 'komisaris'))->first()
            ?? User::skip(1)->first()
            ?? $manager;

        // Ambil beberapa produk untuk item rincian
        $sampleProducts = $products->take(6)->values();

        // -------------------------------------------------------------
        // Skenario 1: REQ-202610-0001 (PENDING)
        // Disiapkan agar role Komisaris langsung punya antrean berkas untuk demo approve/reject
        // -------------------------------------------------------------
        $p1 = $sampleProducts->get(0) ?? $products->first();
        $p2 = $sampleProducts->get(1) ?? $products->first();

        $reqPending = RestockRequest::firstOrCreate(
            ['request_no' => 'REQ-202610-0001'],
            [
                'id' => (string) Str::uuid(),
                'requested_by' => $manager->id,
                'status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_notes' => null,
                'general_notes' => 'Pengajuan restock buffer pengadaan stok Q4 hasil peramalan Holt DES. Prioritas tinggi untuk antisipasi lonjakan permintaan.',
                'total_estimated_cost' => 0,
                'created_at' => Carbon::now()->subHours(2),
                'updated_at' => Carbon::now()->subHours(2),
            ]
        );

        // Hapus detail lama jika ada untuk idempotensi seeder
        $reqPending->details()->delete();

        $subtotal1 = 150 * (float) $p1->unit_price;
        $subtotal2 = 80 * (float) $p2->unit_price;

        RestockRequestDetail::create([
            'id' => (string) Str::uuid(),
            'restock_request_id' => $reqPending->id,
            'product_id' => $p1->id,
            'current_stock' => (int) $p1->current_stock,
            'safety_stock' => (int) $p1->minimum_stock,
            'suggested_quantity' => 150,
            'requested_quantity' => 150,
            'approved_quantity' => null,
            'estimated_unit_price' => (float) $p1->unit_price,
            'subtotal' => $subtotal1,
            'notes' => 'Stok mendekati batas aman, butuh penambahan segera.',
        ]);

        RestockRequestDetail::create([
            'id' => (string) Str::uuid(),
            'restock_request_id' => $reqPending->id,
            'product_id' => $p2->id,
            'current_stock' => (int) $p2->current_stock,
            'safety_stock' => (int) $p2->minimum_stock,
            'suggested_quantity' => 80,
            'requested_quantity' => 80,
            'approved_quantity' => null,
            'estimated_unit_price' => (float) $p2->unit_price,
            'subtotal' => $subtotal2,
            'notes' => 'Buffer akhir bulan reguler.',
        ]);

        $reqPending->update(['total_estimated_cost' => $subtotal1 + $subtotal2]);

        ActivityLog::firstOrCreate(
            [
                'log_name' => 'restock',
                'event' => 'created',
                'subject_type' => RestockRequest::class,
                'subject_id' => $reqPending->id,
            ],
            [
                'causer_type' => User::class,
                'causer_id' => $manager->id,
                'description' => "Pengajuan pengadaan {$reqPending->request_no} diajukan oleh {$manager->name}",
                'created_at' => $reqPending->created_at,
            ]
        );

        // -------------------------------------------------------------
        // Skenario 2: REQ-202610-0002 (APPROVED)
        // Disiapkan agar Staf Gudang dapat langsung menarik data ini saat membuka form Catat Barang Masuk
        // -------------------------------------------------------------
        $p3 = $sampleProducts->get(2) ?? $sampleProducts->get(0);
        $p4 = $sampleProducts->get(3) ?? $sampleProducts->get(1);

        $reqApproved = RestockRequest::firstOrCreate(
            ['request_no' => 'REQ-202610-0002'],
            [
                'id' => (string) Str::uuid(),
                'requested_by' => $manager->id,
                'status' => 'approved',
                'reviewed_by' => $commissioner->id,
                'reviewed_at' => Carbon::now()->subHours(6),
                'review_notes' => 'Disetujui penuh sesuai alokasi anggaran operasional logistik Q4. Segera proses penerimaan fisik di gudang begitu vendor tiba.',
                'general_notes' => 'Pengadaan reguler barang fast-moving berdasarkan rekomendasi Holt DES.',
                'total_estimated_cost' => 0,
                'created_at' => Carbon::now()->subHours(8),
                'updated_at' => Carbon::now()->subHours(6),
            ]
        );

        $reqApproved->details()->delete();

        $subtotal3 = 200 * (float) $p3->unit_price;
        $subtotal4 = 120 * (float) $p4->unit_price;

        RestockRequestDetail::create([
            'id' => (string) Str::uuid(),
            'restock_request_id' => $reqApproved->id,
            'product_id' => $p3->id,
            'current_stock' => (int) $p3->current_stock,
            'safety_stock' => (int) $p3->minimum_stock,
            'suggested_quantity' => 200,
            'requested_quantity' => 200,
            'approved_quantity' => 200,
            'estimated_unit_price' => (float) $p3->unit_price,
            'subtotal' => $subtotal3,
            'notes' => 'Pesanan batch vendor PT Mitra Distribusi Nusantara.',
        ]);

        RestockRequestDetail::create([
            'id' => (string) Str::uuid(),
            'restock_request_id' => $reqApproved->id,
            'product_id' => $p4->id,
            'current_stock' => (int) $p4->current_stock,
            'safety_stock' => (int) $p4->minimum_stock,
            'suggested_quantity' => 120,
            'requested_quantity' => 120,
            'approved_quantity' => 120,
            'estimated_unit_price' => (float) $p4->unit_price,
            'subtotal' => $subtotal4,
            'notes' => 'Barang masuk batch pengiriman 1.',
        ]);

        $reqApproved->update(['total_estimated_cost' => $subtotal3 + $subtotal4]);

        ActivityLog::firstOrCreate(
            [
                'log_name' => 'restock',
                'event' => 'approved',
                'subject_type' => RestockRequest::class,
                'subject_id' => $reqApproved->id,
            ],
            [
                'causer_type' => User::class,
                'causer_id' => $commissioner->id,
                'description' => "Pengajuan pengadaan {$reqApproved->request_no} disetujui oleh {$commissioner->name}",
                'created_at' => $reqApproved->reviewed_at,
            ]
        );

        // -------------------------------------------------------------
        // Skenario 3: REQ-202610-0003 (REJECTED)
        // Disiapkan untuk pembuktian jejak audit penolakan oleh Komisaris
        // -------------------------------------------------------------
        $p5 = $sampleProducts->get(4) ?? $sampleProducts->get(0);
        $p6 = $sampleProducts->get(5) ?? $sampleProducts->get(1);

        $reqRejected = RestockRequest::firstOrCreate(
            ['request_no' => 'REQ-202610-0003'],
            [
                'id' => (string) Str::uuid(),
                'requested_by' => $manager->id,
                'status' => 'rejected',
                'reviewed_by' => $commissioner->id,
                'reviewed_at' => Carbon::now()->subDays(1),
                'review_notes' => 'Kuantitas pengadaan melampaui batas plafon anggaran logistik mingguan. Harap ajukan ulang dengan penyesuaian kuantitas.',
                'general_notes' => 'Pengajuan pengadaan volume ekstra besar untuk cadangan akhir tahun.',
                'total_estimated_cost' => 0,
                'created_at' => Carbon::now()->subDays(1)->subHours(3),
                'updated_at' => Carbon::now()->subDays(1),
            ]
        );

        $reqRejected->details()->delete();

        $subtotal5 = 1000 * (float) $p5->unit_price;
        $subtotal6 = 850 * (float) $p6->unit_price;

        RestockRequestDetail::create([
            'id' => (string) Str::uuid(),
            'restock_request_id' => $reqRejected->id,
            'product_id' => $p5->id,
            'current_stock' => (int) $p5->current_stock,
            'safety_stock' => (int) $p5->minimum_stock,
            'suggested_quantity' => 1000,
            'requested_quantity' => 1000,
            'approved_quantity' => 0,
            'estimated_unit_price' => (float) $p5->unit_price,
            'subtotal' => $subtotal5,
            'notes' => 'Pengajuan ditolak karena anggaran dialihkan ke operasional pemeliharaan rak.',
        ]);

        RestockRequestDetail::create([
            'id' => (string) Str::uuid(),
            'restock_request_id' => $reqRejected->id,
            'product_id' => $p6->id,
            'current_stock' => (int) $p6->current_stock,
            'safety_stock' => (int) $p6->minimum_stock,
            'suggested_quantity' => 850,
            'requested_quantity' => 850,
            'approved_quantity' => 0,
            'estimated_unit_price' => (float) $p6->unit_price,
            'subtotal' => $subtotal6,
            'notes' => 'Kuantitas melebihi kapasitas dermaga gudang B.',
        ]);

        $reqRejected->update(['total_estimated_cost' => $subtotal5 + $subtotal6]);

        ActivityLog::firstOrCreate(
            [
                'log_name' => 'restock',
                'event' => 'rejected',
                'subject_type' => RestockRequest::class,
                'subject_id' => $reqRejected->id,
            ],
            [
                'causer_type' => User::class,
                'causer_id' => $commissioner->id,
                'description' => "Pengajuan pengadaan {$reqRejected->request_no} ditolak oleh {$commissioner->name}",
                'created_at' => $reqRejected->reviewed_at,
            ]
        );

        $this->command?->info('Seeder alur approval pengadaan restock (Pending, Approved, Rejected) berhasil dieksekusi.');
    }
}
