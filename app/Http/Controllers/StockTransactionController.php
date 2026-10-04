<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\StockTransactionDetail;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StockTransactionController extends Controller
{
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

        $generatedRef = 'TRX-IN-' . date('Ymd') . '-' . strtoupper(Str::random(4));

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

        DB::transaction(function () use ($validated) {
            $transaction = StockTransaction::create([
                'reference_no' => $validated['reference_no'],
                'type' => 'inbound',
                'transaction_date' => $validated['transaction_date'],
                'party_name' => $validated['party_name'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                // Lock row produk untuk konsistensi
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();

                StockTransactionDetail::create([
                    'stock_transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? $product->unit_price,
                    'notes' => $item['notes'] ?? null,
                ]);

                // Tambah kuantitas stok produk
                $product->increment('current_stock', $item['quantity']);
            }
        });

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

        $generatedRef = 'TRX-OUT-' . date('Ymd') . '-' . strtoupper(Str::random(4));

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

        DB::transaction(function () use ($validated) {
            // Validasi ketersediaan stok fisik riil
            foreach ($validated['items'] as $item) {
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();
                if ($product->current_stock < $item['quantity']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => "Stok produk {$product->name} (SKU: {$product->sku}) tidak mencukupi. Sisa stok: {$product->current_stock}, diminta: {$item['quantity']}.",
                    ]);
                }
            }

            $transaction = StockTransaction::create([
                'reference_no' => $validated['reference_no'],
                'type' => 'outbound',
                'transaction_date' => $validated['transaction_date'],
                'party_name' => $validated['party_name'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();

                StockTransactionDetail::create([
                    'stock_transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? $product->unit_price,
                    'notes' => $item['notes'] ?? null,
                ]);

                // Kurangi kuantitas stok produk
                $product->decrement('current_stock', $item['quantity']);
            }
        });

        return redirect()->route('transactions.index')->with('success', 'Transaksi pengeluaran barang berhasil disimpan dan stok telah dikurangi.');
    }
}
