<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\DoubleExponentialSmoothingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        protected DoubleExponentialSmoothingService $desService
    ) {}

    /**
     * Tampilkan Dasbor Analitik Peramalan DES
     */
    public function index(Request $request): Response
    {
        $products = Product::query()
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'category_id', 'sku', 'name', 'unit', 'current_stock', 'minimum_stock']);

        $selectedProductId = $request->query('product_id', $products->first()?->id);
        $selectedProduct = $products->firstWhere('id', $selectedProductId) ?? $products->first();

        $forecastData = null;
        if ($selectedProduct) {
            $manualAlpha = $request->filled('alpha') ? (float) $request->query('alpha') : null;
            $manualBeta = $request->filled('beta') ? (float) $request->query('beta') : null;

            $forecastData = $this->desService->forecastProduct(
                $selectedProduct,
                $manualAlpha,
                $manualBeta,
                horizon: 3
            );
        }

        // Susun tabel rekomendasi restock ringkas untuk seluruh master produk
        $restockAlerts = $products->map(function ($p) {
            $forecast = $this->desService->forecastProduct($p, horizon: 1);
            $rec = $forecast['restock_recommendation'];

            return [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'category_name' => $p->category?->name ?? 'Uncategorized',
                'current_stock' => (int) $p->current_stock,
                'minimum_stock' => (int) $p->minimum_stock,
                'unit' => $p->unit,
                'next_forecast' => (int) round($forecast['future_forecasts'][0] ?? 0),
                'suggested_quantity' => $rec['suggested_quantity'],
                'status' => $rec['status'],
                'status_label' => $rec['status_label'],
                'mape' => $forecast['mape'],
                'rmse' => $forecast['rmse'],
            ];
        })->sortByDesc(fn ($item) => $item['status'] === 'RESTOCK_URGENT' ? 2 : ($item['suggested_quantity'] > 0 ? 1 : 0))->values();

        return Inertia::render('Analytics/Index', [
            'products' => $products,
            'selectedProductId' => $selectedProductId,
            'forecastData' => $forecastData,
            'restockAlerts' => $restockAlerts,
            'filters' => [
                'alpha' => $request->query('alpha'),
                'beta' => $request->query('beta'),
            ],
        ]);
    }

    /**
     * Endpoint Simulasi Cepat DES via AJAX/JSON
     */
    public function simulate(Request $request, string $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $alpha = $request->filled('alpha') ? (float) $request->input('alpha') : null;
        $beta = $request->filled('beta') ? (float) $request->input('beta') : null;
        $horizon = (int) $request->input('horizon', 3);

        $forecast = $this->desService->forecastProduct($product, $alpha, $beta, $horizon);

        return response()->json([
            'success' => true,
            'data' => $forecast,
        ]);
    }
}
