<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\StockTransactionDetail;
use App\Services\StockTransactionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StockTransactionController extends Controller
{
    public function __construct(
        protected StockTransactionService $stockTransactionService
    ) {}

    /**
     * Menampilkan riwayat transaksi mutasi stok.
     */
    public function index(Request $request): Response
    {
        $type = $request->input('type'); // inbound, outbound, or null (all)
        $search = $request->input('search');

        $query = StockTransaction::query()
            ->with(['creator:id,name', 'details.product:id,sku,name,unit,unit_price'])
            ->withCount('details');

        if ($type && in_array($type, ['inbound', 'outbound'])) {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhere('party_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('details.product', function ($pq) use ($search) {
                        $pq->where('sku', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_inbound' => StockTransaction::where('type', 'inbound')->count(),
            'total_outbound' => StockTransaction::where('type', 'outbound')->count(),
            'today_inbound_qty' => StockTransactionDetail::whereHas('transaction', function ($q) {
                $q->where('type', 'inbound')->whereDate('transaction_date', Carbon::today());
            })->sum('quantity'),
            'today_outbound_qty' => StockTransactionDetail::whereHas('transaction', function ($q) {
                $q->where('type', 'outbound')->whereDate('transaction_date', Carbon::today());
            })->sum('quantity'),
        ];

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'filters' => [
                'type' => $type ?? '',
                'search' => $search ?? '',
            ],
            'stats' => $stats,
            'can_create' => Auth::user()?->hasAnyRole(['staf-gudang', 'manajer-operasional']) || Auth::user()?->can('transactions.create'),
        ]);
    }

    /**
     * Menampilkan form pencatatan barang masuk (Inbound).
     */
    public function inboundCreate(): Response
    {
        $products = Product::query()
            ->with('category:id,name')
            ->select('id', 'category_id', 'sku', 'name', 'unit', 'unit_price', 'current_stock', 'minimum_stock')
            ->orderBy('name')
            ->get();

        $generatedRef = $this->stockTransactionService->generateReferenceNumber('inbound');

        return Inertia::render('Transactions/InboundCreate', [
            'products' => $products,
            'categories' => Category::select('id', 'name')->orderBy('name')->get(),
            'generatedRef' => $generatedRef,
        ]);
    }

    /**
     * Menyimpan data transaksi barang masuk (Inbound) secara atomik.
     */
    public function inboundStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference_no' => ['required', 'string', 'max:100'],
            'transaction_date' => ['required', 'date'],
            'party_name' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'items.required' => 'Minimal pilih 1 item barang yang akan dicatat.',
            'items.*.quantity.min' => 'Jumlah barang masuk minimal 1.',
        ]);

        $this->stockTransactionService->createInbound($validated, Auth::user(), [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('transactions.index')->with('success', 'Transaksi barang masuk berhasil dicatat dan stok telah diperbarui.');
    }

    /**
     * Menampilkan form pengeluaran barang keluar (Outbound).
     */
    public function outboundCreate(): Response
    {
        $products = Product::query()
            ->with('category:id,name')
            ->select('id', 'category_id', 'sku', 'name', 'unit', 'unit_price', 'current_stock', 'minimum_stock')
            ->orderBy('name')
            ->get();

        $generatedRef = $this->stockTransactionService->generateReferenceNumber('outbound');

        return Inertia::render('Transactions/OutboundCreate', [
            'products' => $products,
            'categories' => Category::select('id', 'name')->orderBy('name')->get(),
            'generatedRef' => $generatedRef,
        ]);
    }

    /**
     * Menyimpan data pengeluaran barang keluar (Outbound) dengan validasi stok riil & locking.
     */
    public function outboundStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference_no' => ['required', 'string', 'max:100'],
            'transaction_date' => ['required', 'date'],
            'party_name' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'items.required' => 'Minimal pilih 1 item barang yang akan dikeluarkan.',
            'items.*.quantity.min' => 'Jumlah barang keluar minimal 1.',
        ]);

        $this->stockTransactionService->createOutbound($validated, Auth::user(), [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('transactions.index')->with('success', 'Transaksi pengeluaran barang berhasil disimpan dan stok telah dikurangi.');
    }

    /**
     * Mengunduh bukti transaksi dalam format PDF.
     */
    public function receiptPdf(StockTransaction $transaction)
    {
        $transaction->load(['details.product', 'creator']);

        $pdf = Pdf::loadView('receipts.transaction', compact('transaction'))
            ->setPaper('a4', 'portrait');

        $filename = 'Bukti-'.$transaction->reference_no.'.pdf';

        return $pdf->stream($filename);
    }
}
