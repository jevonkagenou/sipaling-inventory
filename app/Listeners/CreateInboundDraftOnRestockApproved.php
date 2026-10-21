<?php

namespace App\Listeners;

use App\Events\RestockRequestApproved;
use App\Services\StockTransactionService;
use Illuminate\Support\Facades\Log;

class CreateInboundDraftOnRestockApproved
{
    /**
     * Inisiasi event listener.
     */
    public function __construct(
        protected StockTransactionService $stockTransactionService
    ) {}

    /**
     * Tangani event persetujuan dokumen pengadaan restock.
     */
    public function handle(RestockRequestApproved $event): void
    {
        // Hanya terbitkan transaksi Inbound jika opsi auto-generate aktif dan terdapat rincian barang
        if (! $event->autoGenerateInbound || empty($event->items)) {
            return;
        }

        try {
            $this->stockTransactionService->createInbound([
                'party_name' => "Pengadaan Restock {$event->request->request_no}",
                'notes' => "Draf transaksi masuk (Inbound) otomatis dari persetujuan restock {$event->request->request_no}",
                'items' => $event->items,
            ], $event->reviewer, $event->meta);
        } catch (\Throwable $e) {
            Log::error("Gagal membuat draf Inbound otomatis untuk restock {$event->request->request_no}: {$e->getMessage()}", [
                'request_id' => $event->request->id,
                'reviewer_id' => $event->reviewer->id,
            ]);
            throw $e;
        }
    }
}
