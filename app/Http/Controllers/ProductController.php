<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\StockTransactionDetail;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $query = Product::query()
            ->with('category:id,name,slug')
            ->withCount('transactionDetails');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
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
            'categories' => Category::select('id', 'name')->orderBy('name')->get(),
            'filters' => [
                'search' => $search ?? '',
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
}
