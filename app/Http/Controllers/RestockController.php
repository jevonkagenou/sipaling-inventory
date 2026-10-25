<?php

namespace App\Http\Controllers;

use App\Models\RestockRequest;
use App\Services\RestockRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RestockController extends Controller
{
    public function __construct(
        protected RestockRequestService $restockService
    ) {}

    /**
     * Tampilkan Halaman Monitoring & Pengajuan Draf Restock
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', RestockRequest::class);

        $status = $request->input('status');
        $search = $request->input('search');

        $query = RestockRequest::query()
            ->with([
                'requester:id,name,email',
                'reviewer:id,name,email',
                'details.product:id,sku,name,unit,unit_price,current_stock,minimum_stock',
            ])
            ->withCount('details');

        if ($status && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('request_no', 'like', "%{$search}%")
                    ->orWhere('general_notes', 'like', "%{$search}%")
                    ->orWhere('review_notes', 'like', "%{$search}%")
                    ->orWhereHas('requester', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('reviewer', function ($rq) use ($search) {
                        $rq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('details.product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            });
        }

        $requests = $query->orderByDesc('created_at')->get();

        $stats = [
            'pending' => RestockRequest::where('status', 'pending')->count(),
            'approved' => RestockRequest::where('status', 'approved')->count(),
            'rejected' => RestockRequest::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Restock/Index', [
            'requests' => $requests,
            'filters' => [
                'status' => $status ?? 'all',
                'search' => $search ?? '',
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Menyimpan Berkas Usulan Restock Multi-Item Baru (Manajer Operasional)
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', RestockRequest::class);

        $validated = $request->validate([
            'general_notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.requested_quantity' => ['required', 'integer', 'min:1'],
            'items.*.suggested_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.estimated_unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'items.required' => 'Minimal pilih 1 produk untuk mengajukan restock.',
            'items.min' => 'Minimal pilih 1 produk untuk mengajukan restock.',
            'items.*.requested_quantity.min' => 'Jumlah barang yang diminta minimal 1 unit.',
        ]);

        $restock = $this->restockService->createRequest($validated, $request->user(), [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('restock.index')->with(
            'success',
            "Pengajuan restock {$restock->request_no} berhasil diterbitkan dan menunggu keputusan persetujuan Komisaris."
        );
    }

    /**
     * Tampilkan Halaman Otorisasi Persetujuan Khusus (Komisaris Only)
     */
    public function approval(Request $request): Response
    {
        Gate::authorize('viewAny', RestockRequest::class);

        $pendingRequests = RestockRequest::query()
            ->pending()
            ->with([
                'requester:id,name,email',
                'details.product:id,sku,name,unit,unit_price,current_stock,minimum_stock',
            ])
            ->withCount('details')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Restock/Approval', [
            'requests' => $pendingRequests,
        ]);
    }

    /**
     * Menyetujui Berkas Pengadaan Restock & Menerbitkan Transaksi Inbound Otomatis (Komisaris Only)
     */
    public function approve(Request $request, RestockRequest $restockRequest): RedirectResponse
    {
        Gate::authorize('approve', $restockRequest);

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $notes = $validated['review_notes'] ?? 'Disetujui penuh oleh Komisaris sesuai rekomendasi peramalan Holt DES.';

        $this->restockService->approve(
            request: $restockRequest,
            reviewer: $request->user(),
            notes: $notes,
            meta: [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            autoGenerateInbound: true
        );

        return back()->with(
            'success',
            "Berkas pengadaan {$restockRequest->request_no} berhasil disetujui dan transaksi masuk (Inbound) gudang telah diterbitkan secara otomatis."
        );
    }

    /**
     * Menolak Berkas Pengadaan Restock dengan Catatan Evaluasi Wajib (Komisaris Only)
     */
    public function reject(Request $request, RestockRequest $restockRequest): RedirectResponse
    {
        Gate::authorize('reject', $restockRequest);

        $validated = $request->validate([
            'review_notes' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'review_notes.required' => 'Catatan arahan / alasan penolakan wajib dilampirkan oleh Komisaris.',
            'review_notes.min' => 'Catatan penolakan minimal 3 karakter.',
        ]);

        $this->restockService->reject(
            request: $restockRequest,
            reviewer: $request->user(),
            reason: $validated['review_notes'],
            meta: [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        return back()->with(
            'success',
            "Berkas pengadaan {$restockRequest->request_no} telah ditolak dengan catatan evaluasi."
        );
    }
}
