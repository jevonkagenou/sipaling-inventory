<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\StockTransactionDetail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockTransactionService
{
    /**
     * Menghasilkan nomor referensi dokumen mutasi otomatis berformat standar (TRX-IN / TRX-OUT).
     * Format: TRX-{IN|OUT}-YYYYMMDD-XXXX (4-digit sekuensial unik per hari).
     */
    public function generateReferenceNumber(string $type): string
    {
        $normalizedType = strtolower($type);

        if (! in_array($normalizedType, ['inbound', 'outbound'], true)) {
            throw new InvalidArgumentException("Tipe transaksi tidak valid: {$type}. Gunakan 'inbound' atau 'outbound'.");
        }

        $prefixType = $normalizedType === 'inbound' ? 'IN' : 'OUT';
        $datePart = now()->format('Ymd');
        $prefix = "TRX-{$prefixType}-{$datePart}-";

        // Cari transaksi terakhir pada hari ini dengan tipe & prefix yang sama
        $lastTransaction = StockTransaction::where('type', $normalizedType)
            ->where('reference_no', 'like', "{$prefix}%")
            ->orderByDesc('reference_no')
            ->first();

        $sequence = 1;
        if ($lastTransaction) {
            $lastSuffix = substr($lastTransaction->reference_no, strlen($prefix));
            if (is_numeric($lastSuffix)) {
                $sequence = (int) $lastSuffix + 1;
            } else {
                $sequence = StockTransaction::where('type', $normalizedType)
                    ->where('reference_no', 'like', "{$prefix}%")
                    ->count() + 1;
            }
        }

        $ref = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

        // Pastikan tidak ada duplikasi nomor referensi (garansi keunikan)
        while (StockTransaction::where('reference_no', $ref)->exists()) {
            $sequence++;
            $ref = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        }

        return $ref;
    }

    /**
     * Mencatat transaksi barang masuk (Inbound) secara atomik.
     * Menggunakan DB::transaction: seluruh perubahan dibatalkan (rollback) otomatis jika terjadi kegagalan sistem.
     *
     * @param  array{
     *     reference_no?: string,
     *     transaction_date?: string,
     *     party_name?: string|null,
     *     notes?: string|null,
     *     items: array<array{product_id: string, quantity: int, unit_price?: float|null, notes?: string|null}>
     * }  $data
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function createInbound(array $data, ?User $user = null, array $meta = []): StockTransaction
    {
        if (empty($data['items'])) {
            throw ValidationException::withMessages([
                'items' => 'Minimal pilih 1 item barang yang akan dicatat.',
            ]);
        }

        $creator = $user ?? Auth::user();

        return DB::transaction(function () use ($data, $creator, $meta) {
            $referenceNo = $data['reference_no'] ?? $this->generateReferenceNumber('inbound');

            $transaction = StockTransaction::create([
                'reference_no' => $referenceNo,
                'type' => 'inbound',
                'transaction_date' => $data['transaction_date'] ?? now(),
                'party_name' => $data['party_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $creator?->id,
            ]);

            $totalQuantity = 0;
            $itemSummaries = [];

            // Urutkan item berdasarkan product_id secara konsisten untuk mencegah potensi deadlock saat konkurensi tinggi
            $sortedItems = collect($data['items'])->sortBy('product_id')->values()->all();

            foreach ($sortedItems as $item) {
                // Lock row produk secara pesimistik untuk konsistensi & pencegahan race condition
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();

                StockTransactionDetail::create([
                    'stock_transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? $product->unit_price,
                    'notes' => $item['notes'] ?? null,
                ]);

                // Tambahkan kuantitas stok produk
                $product->increment('current_stock', (int) $item['quantity']);

                $totalQuantity += (int) $item['quantity'];
                $itemSummaries[] = [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'quantity' => (int) $item['quantity'],
                    'unit' => $product->unit,
                ];
            }

            // Catat audit log jika activitylog aktif
            $this->logActivity('inbound', $transaction, $creator, $totalQuantity, $itemSummaries, $meta);

            return $transaction->load(['details.product', 'creator']);
        });
    }

    /**
     * Mencatat transaksi pengeluaran barang (Outbound) secara atomik.
     * Menerapkan lockForUpdate dan validasi stok riil untuk mencegah stok minus.
     * Menggunakan DB::transaction: dibatalkan (rollback) otomatis jika terjadi kegagalan sistem.
     *
     * @param  array{
     *     reference_no?: string,
     *     transaction_date?: string,
     *     party_name?: string|null,
     *     notes?: string|null,
     *     items: array<array{product_id: string, quantity: int, unit_price?: float|null, notes?: string|null}>
     * }  $data
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function createOutbound(array $data, ?User $user = null, array $meta = []): StockTransaction
    {
        if (empty($data['items'])) {
            throw ValidationException::withMessages([
                'items' => 'Minimal pilih 1 item barang yang akan dikeluarkan.',
            ]);
        }

        $creator = $user ?? Auth::user();

        return DB::transaction(function () use ($data, $creator, $meta) {
            // Tahap 1: Hitung akumulasi kebutuhan total per produk (mencegah duplikasi item lolos validasi)
            $requiredPerProduct = [];
            foreach ($data['items'] as $item) {
                $pId = $item['product_id'];
                $requiredPerProduct[$pId] = ($requiredPerProduct[$pId] ?? 0) + (int) $item['quantity'];
            }

            // Urutkan product_id secara konsisten untuk mencegah deadlock pada transaksi paralel
            ksort($requiredPerProduct);

            // Tahap 2: Validasi stok fisik riil dengan pessimistic row locking (lockForUpdate)
            $lockedProducts = [];
            foreach ($requiredPerProduct as $productId => $totalRequired) {
                $product = Product::where('id', $productId)->lockForUpdate()->firstOrFail();

                if ($product->current_stock < $totalRequired) {
                    throw ValidationException::withMessages([
                        'items' => "Stok produk {$product->name} (SKU: {$product->sku}) tidak mencukupi. Sisa stok: {$product->current_stock}, diminta: {$totalRequired}.",
                    ]);
                }

                $lockedProducts[$productId] = $product;
            }

            $referenceNo = $data['reference_no'] ?? $this->generateReferenceNumber('outbound');

            $transaction = StockTransaction::create([
                'reference_no' => $referenceNo,
                'type' => 'outbound',
                'transaction_date' => $data['transaction_date'] ?? now(),
                'party_name' => $data['party_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $creator?->id,
            ]);

            $totalQuantity = 0;
            $itemSummaries = [];

            foreach ($data['items'] as $item) {
                $product = $lockedProducts[$item['product_id']] ?? Product::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();

                StockTransactionDetail::create([
                    'stock_transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? $product->unit_price,
                    'notes' => $item['notes'] ?? null,
                ]);

                // Kurangi kuantitas stok produk
                $product->decrement('current_stock', (int) $item['quantity']);

                $totalQuantity += (int) $item['quantity'];
                $itemSummaries[] = [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'quantity' => (int) $item['quantity'],
                    'unit' => $product->unit,
                ];
            }

            // Catat audit log jika activitylog aktif
            $this->logActivity('outbound', $transaction, $creator, $totalQuantity, $itemSummaries, $meta);

            return $transaction->load(['details.product', 'creator']);
        });
    }

    /**
     * Mencatat transaksi mutasi stok sesuai tipe ('inbound' atau 'outbound').
     */
    public function recordTransaction(string $type, array $data, ?User $user = null, array $meta = []): StockTransaction
    {
        return match (strtolower($type)) {
            'inbound' => $this->createInbound($data, $user, $meta),
            'outbound' => $this->createOutbound($data, $user, $meta),
            default => throw new InvalidArgumentException("Tipe mutasi stok tidak dikenali: {$type}"),
        };
    }

    /**
     * Bantuan pencatatan jejak audit (ActivityLog) terintegrasi Spatie.
     */
    protected function logActivity(
        string $event,
        StockTransaction $transaction,
        ?User $user,
        int $totalQuantity,
        array $itemSummaries,
        array $meta
    ): void {
        if (! function_exists('activity')) {
            return;
        }

        $label = $event === 'inbound' ? 'penerimaan barang masuk (Inbound)' : 'pengeluaran barang (Outbound)';

        activity('inventory')
            ->performedOn($transaction)
            ->causedBy($user)
            ->event($event)
            ->withProperties([
                'reference_no' => $transaction->reference_no,
                'party_name' => $transaction->party_name,
                'total_quantity' => $totalQuantity,
                'items_count' => count($itemSummaries),
                'items' => $itemSummaries,
                'ip' => $meta['ip'] ?? request()?->ip(),
                'user_agent' => $meta['user_agent'] ?? request()?->userAgent(),
            ])
            ->log("Mencatat {$label}: Ref {$transaction->reference_no} ({$totalQuantity} unit)");
    }
}
