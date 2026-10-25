<?php

namespace App\Services;

use App\Events\RestockRequestApproved;
use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\RestockRequestDetail;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RestockRequestService
{
    public function __construct(
        protected ?StockTransactionService $stockTransactionService = null
    ) {
        $this->stockTransactionService = $stockTransactionService ?? app(StockTransactionService::class);
    }

    /**
     * Menghasilkan nomor referensi pengadaan restock otomatis (REQ-YYYYMM-XXXX).
     * Menerapkan lockForUpdate untuk mencegah race condition (duplikasi nomor pengajuan).
     */
    public function generateRequestNumber(): string
    {
        $prefix = 'REQ-'.now()->format('Ym').'-';

        $lastRequest = RestockRequest::where('request_no', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->orderByDesc('request_no')
            ->first();

        $sequence = 1;
        if ($lastRequest) {
            $lastSuffix = substr($lastRequest->request_no, strlen($prefix));
            if (is_numeric($lastSuffix)) {
                $sequence = (int) $lastSuffix + 1;
            } else {
                $sequence = RestockRequest::where('request_no', 'like', "{$prefix}%")->count() + 1;
            }
        }

        $ref = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

        while (RestockRequest::where('request_no', $ref)->lockForUpdate()->exists()) {
            $sequence++;
            $ref = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        }

        return $ref;
    }

    /**
     * Memeriksa apakah transisi status diizinkan berdasarkan aturan Anti-Bypass.
     */
    public function canTransitionTo(RestockRequest $request, string $targetStatus): bool
    {
        $allowedTransitions = [
            'pending' => ['approved', 'rejected'],
            'approved' => [],
            'rejected' => [],
        ];

        return in_array($targetStatus, $allowedTransitions[$request->status] ?? [], true);
    }

    /**
     * Membuat berkas pengajuan restock baru berstatus pending.
     *
     * @param  array{
     *     request_no?: string,
     *     total_estimated_cost?: float|int,
     *     general_notes?: string|null,
     *     items?: array<array{product_id: string, requested_quantity?: int, current_stock?: int, safety_stock?: int, suggested_quantity?: int, estimated_unit_price?: float|null, notes?: string|null}>
     * }  $data
     */
    public function createRequest(array $data, User $requester, array $meta = []): RestockRequest
    {
        return DB::transaction(function () use ($data, $requester, $meta) {
            $requestNo = $data['request_no'] ?? $this->generateRequestNumber();

            $restockRequest = RestockRequest::create([
                'request_no' => $requestNo,
                'requested_by' => $requester->id,
                'status' => 'pending',
                'total_estimated_cost' => $data['total_estimated_cost'] ?? 0,
                'general_notes' => $data['general_notes'] ?? null,
            ]);

            $calculatedTotalCost = 0;

            if (! empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $requestedQty = (int) ($item['requested_quantity'] ?? 1);
                    $unitPrice = isset($item['estimated_unit_price']) ? (float) $item['estimated_unit_price'] : (float) $product->unit_price;
                    $subtotal = $requestedQty * $unitPrice;
                    $calculatedTotalCost += $subtotal;

                    RestockRequestDetail::create([
                        'restock_request_id' => $restockRequest->id,
                        'product_id' => $product->id,
                        'current_stock' => $item['current_stock'] ?? $product->current_stock,
                        'safety_stock' => $item['safety_stock'] ?? $product->minimum_stock,
                        'suggested_quantity' => $item['suggested_quantity'] ?? 0,
                        'requested_quantity' => $requestedQty,
                        'approved_quantity' => null,
                        'estimated_unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                if ($calculatedTotalCost > 0 && empty($data['total_estimated_cost'])) {
                    $restockRequest->update([
                        'total_estimated_cost' => $calculatedTotalCost,
                    ]);
                }
            }

            $this->logActivity('restock_requested', $restockRequest, $requester, "Menerbitkan pengajuan restock {$restockRequest->request_no}", $meta);

            return $restockRequest->load(['requester', 'details.product']);
        });
    }

    /**
     * Menyetujui berkas pengadaan restock oleh Komisaris (Anti-Bypass).
     * Menerapkan pessimistic lock (lockForUpdate) dan integrasi atomik transaksi masuk (Inbound).
     *
     * @param  array<array{product_id: string, quantity: int, unit_price?: float|null, notes?: string|null}>  $inboundItems
     */
    public function approve(
        RestockRequest $request,
        User $reviewer,
        ?string $notes = null,
        array $meta = [],
        bool $autoGenerateInbound = false,
        array $inboundItems = []
    ): RestockRequest {
        return DB::transaction(function () use ($request, $reviewer, $notes, $meta, $autoGenerateInbound, $inboundItems) {
            // Lock pesimistik pada baris restock_requests untuk mencegah race condition (double approval / concurrent decision)
            $lockedRequest = RestockRequest::where('id', $request->id)->lockForUpdate()->firstOrFail();

            // Validasi Anti-Bypass: Hanya dokumen berstatus pending yang boleh disetujui
            if (! $this->canTransitionTo($lockedRequest, 'approved')) {
                throw ValidationException::withMessages([
                    'status' => ["Dokumen pengadaan {$lockedRequest->request_no} tidak dapat disetujui karena berstatus {$lockedRequest->status} (Anti-Bypass)."],
                ]);
            }

            $lockedRequest->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ]);

            // Sinkronkan kuantitas yang disetujui pada detail pengajuan
            foreach ($lockedRequest->details as $detail) {
                $detail->update([
                    'approved_quantity' => $detail->requested_quantity,
                ]);
            }

            // Jika autoGenerateInbound aktif dan $inboundItems belum terisi, susun otomatis dari details pengadaan
            if ($autoGenerateInbound && empty($inboundItems) && $lockedRequest->details->isNotEmpty()) {
                $inboundItems = $lockedRequest->details->map(function ($detail) use ($lockedRequest) {
                    return [
                        'product_id' => $detail->product_id,
                        'quantity' => (int) ($detail->approved_quantity ?? $detail->requested_quantity),
                        'unit_price' => (float) $detail->estimated_unit_price,
                        'notes' => "Item otomatis dari pengadaan restock {$lockedRequest->request_no}" . ($detail->notes ? " ({$detail->notes})" : ''),
                    ];
                })->all();
            }

            $this->logActivity('restock_approved', $lockedRequest, $reviewer, "Menyetujui pengajuan restock {$lockedRequest->request_no}", $meta);

            // Memicu Event persetujuan dokumen pengadaan restock secara atomik di dalam transaksi
            event(new RestockRequestApproved(
                request: $lockedRequest,
                reviewer: $reviewer,
                autoGenerateInbound: $autoGenerateInbound,
                items: $inboundItems,
                meta: $meta
            ));

            $request->refresh();

            return $lockedRequest->load(['requester', 'reviewer', 'details.product']);
        });
    }

    /**
     * Menerbitkan draf transaksi Inbound secara langsung dari pengadaan restock yang disetujui.
     *
     * @param  array<array{product_id: string, quantity: int, unit_price?: float|null, notes?: string|null}>  $items
     */
    public function generateInboundDraft(
        RestockRequest $request,
        User $actor,
        array $items,
        array $meta = []
    ): StockTransaction {
        $request->refresh();

        if (! $request->isApproved()) {
            throw ValidationException::withMessages([
                'status' => ["Draf Inbound hanya dapat diterbitkan untuk pengadaan berstatus 'approved'. Status saat ini: {$request->status}."],
            ]);
        }

        return $this->stockTransactionService->createInbound([
            'party_name' => "Pengadaan Restock {$request->request_no}",
            'notes' => "Draf transaksi masuk (Inbound) otomatis dari persetujuan restock {$request->request_no}",
            'items' => $items,
        ], $actor, $meta);
    }

    /**
     * Menolak berkas pengadaan restock oleh Komisaris dengan catatan wajib (Anti-Bypass).
     * Menerapkan pessimistic lock (lockForUpdate) untuk mencegah race condition.
     */
    public function reject(RestockRequest $request, User $reviewer, string $reason, array $meta = []): RestockRequest
    {
        $trimmedReason = trim($reason);

        if (empty($trimmedReason)) {
            throw ValidationException::withMessages([
                'review_notes' => ['Alasan penolakan pengadaan wajib diisi secara jelas oleh Komisaris.'],
            ]);
        }

        return DB::transaction(function () use ($request, $reviewer, $trimmedReason, $meta) {
            // Lock pesimistik pada baris restock_requests untuk mencegah race condition (concurrent decision)
            $lockedRequest = RestockRequest::where('id', $request->id)->lockForUpdate()->firstOrFail();

            // Validasi Anti-Bypass: Hanya dokumen berstatus pending yang boleh ditolak
            if (! $this->canTransitionTo($lockedRequest, 'rejected')) {
                throw ValidationException::withMessages([
                    'status' => ["Dokumen pengadaan {$lockedRequest->request_no} tidak dapat ditolak karena berstatus {$lockedRequest->status} (Anti-Bypass)."],
                ]);
            }

            $lockedRequest->update([
                'status' => 'rejected',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $trimmedReason,
            ]);

            // Sinkronkan approved_quantity = 0 pada detail pengajuan yang ditolak
            foreach ($lockedRequest->details as $detail) {
                $detail->update([
                    'approved_quantity' => 0,
                ]);
            }

            $this->logActivity('restock_rejected', $lockedRequest, $reviewer, "Menolak pengajuan restock {$lockedRequest->request_no}. Alasan: {$trimmedReason}", $meta);

            $request->refresh();

            return $lockedRequest->load(['requester', 'reviewer', 'details.product']);
        });
    }

    /**
     * Bantuan pencatatan jejak audit (ActivityLog) terintegrasi Spatie.
     */
    protected function logActivity(
        string $event,
        RestockRequest $request,
        User $actor,
        string $description,
        array $meta = []
    ): void {
        if (! function_exists('activity')) {
            return;
        }

        activity('restock')
            ->performedOn($request)
            ->causedBy($actor)
            ->event($event)
            ->withProperties([
                'request_no' => $request->request_no,
                'status' => $request->status,
                'total_estimated_cost' => (float) $request->total_estimated_cost,
                'ip' => $meta['ip'] ?? request()?->ip(),
                'user_agent' => $meta['user_agent'] ?? request()?->userAgent(),
            ])
            ->log($description);
    }
}
