<?php

namespace App\Services;

use App\Events\RestockRequestApproved;
use App\Models\RestockRequest;
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
     */
    public function generateRequestNumber(): string
    {
        $prefix = 'REQ-'.now()->format('Ym').'-';

        $lastRequest = RestockRequest::where('request_no', 'like', "{$prefix}%")
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

        while (RestockRequest::where('request_no', $ref)->exists()) {
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

            $this->logActivity('restock_requested', $restockRequest, $requester, "Menerbitkan pengajuan restock {$restockRequest->request_no}", $meta);

            return $restockRequest->load('requester');
        });
    }

    /**
     * Menyetujui berkas pengadaan restock oleh Komisaris (Anti-Bypass).
     * Opsi autoGenerateInbound akan memicu event pembuatan transaksi masuk (Inbound) otomatis.
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
        $approvedRequest = DB::transaction(function () use ($request, $reviewer, $notes, $meta) {
            // Validasi Anti-Bypass: Hanya dokumen berstatus pending yang boleh disetujui
            if (! $this->canTransitionTo($request, 'approved')) {
                throw ValidationException::withMessages([
                    'status' => ["Dokumen pengadaan {$request->request_no} tidak dapat disetujui karena berstatus {$request->status} (Anti-Bypass)."],
                ]);
            }

            $request->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ]);

            $this->logActivity('restock_approved', $request, $reviewer, "Menyetujui pengajuan restock {$request->request_no}", $meta);

            return $request->load(['requester', 'reviewer']);
        });

        // Memicu Event persetujuan dokumen pengadaan restock
        event(new RestockRequestApproved(
            request: $approvedRequest,
            reviewer: $reviewer,
            autoGenerateInbound: $autoGenerateInbound,
            items: $inboundItems,
            meta: $meta
        ));

        return $approvedRequest;
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
            // Validasi Anti-Bypass: Hanya dokumen berstatus pending yang boleh ditolak
            if (! $this->canTransitionTo($request, 'rejected')) {
                throw ValidationException::withMessages([
                    'status' => ["Dokumen pengadaan {$request->request_no} tidak dapat ditolak karena berstatus {$request->status} (Anti-Bypass)."],
                ]);
            }

            $request->update([
                'status' => 'rejected',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $trimmedReason,
            ]);

            $this->logActivity('restock_rejected', $request, $reviewer, "Menolak pengajuan restock {$request->request_no}. Alasan: {$trimmedReason}", $meta);

            return $request->load(['requester', 'reviewer']);
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
