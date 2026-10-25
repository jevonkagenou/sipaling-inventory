<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\StockTransactionDetail;
use App\Services\ProductImportService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $search = $request->input('search');
        $categoryId = $request->input('category_id');

        $query = Product::query()
            ->with('category:id,name,slug')
            ->withCount('transactionDetails');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($categoryId && $categoryId !== 'all') {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
        }

        return Inertia::render('Inventory/Index', [
            'products' => $products,
            'categories' => Category::withCount('products')->orderBy('name')->get(),
            'filters' => [
                'search' => $search ?? '',
                'category_id' => $categoryId ?? 'all',
            ],
            'stats' => [
                'total_products' => Product::count(),
                'total_categories' => Category::count(),
                'total_transactions' => StockTransaction::count(),
                'total_details' => StockTransactionDetail::count(),
                'reorder_count' => Product::whereColumn('current_stock', '<=', 'minimum_stock')->count(),
                'aman_count' => Product::whereColumn('current_stock', '>', 'minimum_stock')->count(),
            ],
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreProductRequest $request): RedirectResponse|JsonResponse
    {
        $product = DB::transaction(function () use ($request) {
            $createdProduct = Product::create($request->validated());

            ActivityLog::create([
                'log_name' => 'inventory',
                'description' => "Menambahkan produk baru: {$createdProduct->name} (SKU: {$createdProduct->sku})",
                'subject_type' => Product::class,
                'subject_id' => $createdProduct->id,
                'causer_type' => Auth::user() ? get_class(Auth::user()) : null,
                'causer_id' => Auth::id(),
                'event' => 'created',
                'properties' => [
                    'attributes' => $createdProduct->toArray(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            return $createdProduct;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Produk berhasil ditambahkan.',
                'product' => $product->load('category:id,name,slug'),
            ], 201);
        }

        return redirect()->route('inventory.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse|JsonResponse
    {
        DB::transaction(function () use ($request, $product) {
            $oldAttributes = $product->only([
                'category_id',
                'sku',
                'name',
                'unit',
                'unit_price',
                'current_stock',
                'minimum_stock',
                'description',
            ]);

            $product->update($request->validated());

            ActivityLog::create([
                'log_name' => 'inventory',
                'description' => "Memperbarui data produk: {$product->name} (SKU: {$product->sku})",
                'subject_type' => Product::class,
                'subject_id' => $product->id,
                'causer_type' => Auth::user() ? get_class(Auth::user()) : null,
                'causer_id' => Auth::id(),
                'event' => 'updated',
                'properties' => [
                    'old' => $oldAttributes,
                    'attributes' => $product->getChanges(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Data produk berhasil diperbarui.',
                'product' => $product->load('category:id,name,slug'),
            ]);
        }

        return redirect()->route('inventory.index')->with('success', 'Data produk berhasil diperbarui.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        // Proteksi restrict delete: produk yang memiliki transaksi tidak boleh dihapus
        if ($product->transactionDetails()->exists()) {
            $errorMessage = 'Produk tidak dapat dihapus karena sudah memiliki riwayat mutasi/transaksi inventaris.';

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $errorMessage,
                ], 422);
            }

            return back()->withErrors(['error' => $errorMessage])->with('error', $errorMessage);
        }

        try {
            DB::transaction(function () use ($request, $product) {
                ActivityLog::create([
                    'log_name' => 'inventory',
                    'description' => "Menghapus produk: {$product->name} (SKU: {$product->sku})",
                    'subject_type' => Product::class,
                    'subject_id' => $product->id,
                    'causer_type' => Auth::user() ? get_class(Auth::user()) : null,
                    'causer_id' => Auth::id(),
                    'event' => 'deleted',
                    'properties' => [
                        'attributes' => $product->toArray(),
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ],
                ]);

                $product->delete();
            });
        } catch (QueryException $e) {
            $errorMessage = 'Produk tidak dapat dihapus karena integritas relasi data di database.';

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $errorMessage,
                ], 422);
            }

            return back()->withErrors(['error' => $errorMessage])->with('error', $errorMessage);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Produk berhasil dihapus.',
            ]);
        }

        return redirect()->route('inventory.index')->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * Mengunduh template berkas CSV untuk impor produk massal.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_produk_sipaling.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            // Menulis UTF-8 BOM untuk kompatibilitas Microsoft Excel
            fputs($handle, "\xEF\xBB\xBF");

            // Header Kolom
            fputcsv($handle, ['sku', 'name', 'category', 'unit', 'unit_price', 'current_stock', 'minimum_stock', 'description']);

            // Baris Data Contoh
            fputcsv($handle, ['BRG-MON-001', 'Monitor LED 24 Inch Full HD', 'Elektronik & Periferal', 'unit', '1850000', '25', '5', 'Monitor LED 1080p HDMI']);
            fputcsv($handle, ['BRG-NET-002', 'Kabel UTP Cat6 305m High Speed', 'Peralatan Jaringan', 'roll', '1200000', '40', '10', 'Kabel LAN UTP Cat6']);
            fputcsv($handle, ['BRG-SWT-003', 'Switch Gigabit 16 Port Unmanaged', 'Peralatan Jaringan', 'unit', '950000', '15', '3', 'Switch 16-Port 10/100/1000Mbps']);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Mengimpor data produk massal dari berkas CSV.
     */
    public function importCsv(Request $request, ProductImportService $importService): RedirectResponse|JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ], [
            'file.required' => 'Berkas CSV wajib dipilih.',
            'file.mimes' => 'Berkas harus berformat .csv atau teks berpemisah koma.',
            'file.max' => 'Ukuran berkas CSV maksimal 5 MB.',
        ]);

        try {
            $result = $importService->import(
                $request->file('file'),
                Auth::user(),
                [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'imported_count' => $result['imported_count'],
                    'skipped_count' => $result['skipped_count'],
                ]);
            }

            return redirect()->route('inventory.index')->with('success', $result['message']);
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['file' => $e->getMessage()])
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengimpor produk: '.$e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['file' => 'Gagal memproses berkas CSV: '.$e->getMessage()])
                ->with('error', 'Gagal memproses berkas CSV: '.$e->getMessage());
        }
    }
}
