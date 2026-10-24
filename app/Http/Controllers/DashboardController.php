<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\StockTransactionDetail;
use App\Models\User;
use App\Services\DoubleExponentialSmoothingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __construct(
        protected DoubleExponentialSmoothingService $desService
    ) {}

    /**
     * Menampilkan dasbor utama dengan ringkasan metrik lintas modul.
     */
    public function index(Request $request): Response
    {
        $today = Carbon::today();

        // dasbor operasional 
        $stats = [
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'total_transactions' => StockTransaction::count(),
            'inbound_transactions' => StockTransaction::where('type', 'inbound')->count(),
            'outbound_transactions' => StockTransaction::where('type', 'outbound')->count(),
            'today_inbound_qty' => (int) StockTransactionDetail::whereHas('transaction', function ($q) use ($today) {
                $q->where('type', 'inbound')->whereDate('transaction_date', $today);
            })->sum('quantity'),
            'today_outbound_qty' => (int) StockTransactionDetail::whereHas('transaction', function ($q) use ($today) {
                $q->where('type', 'outbound')->whereDate('transaction_date', $today);
            })->sum('quantity'),
            'reorder_count' => Product::whereColumn('current_stock', '<=', 'minimum_stock')->count(),
            'aman_count' => Product::whereColumn('current_stock', '>', 'minimum_stock')->count(),
            'total_inventory_value' => Product::all()->sum(fn ($p) => (float) $p->current_stock * (float) $p->unit_price),
            'total_audit_logs' => ActivityLog::count(),
            'today_audit_logs' => ActivityLog::whereDate('created_at', $today)->count(),
        ];

        // 5 produk paling kritis (stok mendekati / di bawah minimum)
        $criticalProducts = Product::with('category:id,name')
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->orderBy('current_stock')
            ->take(5)
            ->get(['id', 'sku', 'name', 'category_id', 'current_stock', 'minimum_stock', 'unit', 'unit_price']);

        // 5 transaksi mutasi stok terakhir
        $recentTransactions = StockTransaction::with(['creator:id,name', 'details.product:id,sku,name'])
            ->withCount('details')
            ->orderBy('transaction_date', 'desc')
            ->take(5)
            ->get();

        // 5 rekaman audit log terakhir
        $recentLogs = ActivityLog::with('causer:id,name,email')
            ->latest()
            ->take(5)
            ->get();

        // 1. Data Tren Mutasi Inbound vs Outbound (14 Hari Terakhir)
        $latestTxDate = StockTransaction::max('transaction_date');
        $anchorDate = Carbon::today();
        if ($latestTxDate && Carbon::parse($latestTxDate)->isBefore($anchorDate->copy()->subDays(14))) {
            $anchorDate = Carbon::parse($latestTxDate);
        }

        $daysCount = 14;
        $startDate = $anchorDate->copy()->subDays($daysCount - 1)->startOfDay();
        $endDate = $anchorDate->copy()->endOfDay();

        $transactionsInRange = StockTransaction::with('details:id,stock_transaction_id,quantity')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get(['id', 'type', 'transaction_date']);

        $mutationTrends = [];
        for ($i = $daysCount - 1; $i >= 0; $i--) {
            $dateObj = $anchorDate->copy()->subDays($i);
            $dateStr = $dateObj->toDateString();
            $label = $dateObj->format('d M');

            $dayTx = $transactionsInRange->filter(function ($tx) use ($dateStr) {
                return Carbon::parse($tx->transaction_date)->toDateString() === $dateStr;
            });

            $inboundQty = (int) $dayTx->where('type', 'inbound')->sum(fn ($tx) => $tx->details->sum('quantity'));
            $outboundQty = (int) $dayTx->where('type', 'outbound')->sum(fn ($tx) => $tx->details->sum('quantity'));
            $inboundCount = (int) $dayTx->where('type', 'inbound')->count();
            $outboundCount = (int) $dayTx->where('type', 'outbound')->count();

            $mutationTrends[] = [
                'date' => $dateStr,
                'label' => $label,
                'inbound_qty' => $inboundQty,
                'outbound_qty' => $outboundQty,
                'inbound_count' => $inboundCount,
                'outbound_count' => $outboundCount,
            ];
        }

        // 2. Data Statistik Status Pengguna (Aktif vs Diblokir & Sebaran Role)
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $blockedUsers = User::where('is_active', false)->count();

        $rolesWithCount = Role::withCount('users')->get()->map(function ($role) {
            return [
                'name' => $role->name,
                'count' => (int) $role->users_count,
            ];
        })->values();

        $userStats = [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'blocked' => $blockedUsers,
            'active_percentage' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0,
            'roles' => $rolesWithCount,
        ];

        // analitik DES - populate data untuk Manajer Operasional
        $products = Product::query()
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'category_id', 'sku', 'name', 'unit', 'unit_price', 'current_stock', 'minimum_stock']);

        // Tentukan produk yang dipilih (dari filter atau default produk pertama)
        $selectedProductId = $request->query('product_id', $products->first()?->id);
        $selectedProduct   = $products->firstWhere('id', $selectedProductId) ?? $products->first();

        // Jalankan DES untuk produk yang dipilih
        $manualAlpha = $request->filled('alpha') ? (float) $request->query('alpha') : null;
        $manualBeta  = $request->filled('beta')  ? (float) $request->query('beta')  : null;

        $forecastData = null;
        if ($selectedProduct) {
            $forecastData = $this->desService->forecastProduct(
                $selectedProduct,
                $manualAlpha,
                $manualBeta,
                horizon: 3
            );
        }

        // Susun ringkasan rekomendasi restock seluruh katalog (sama seperti AnalyticsController)
        $restockAlerts = $products->map(function ($p) {
            $forecast = $this->desService->forecastProduct($p, horizon: 1);
            $rec      = $forecast['restock_recommendation'];

            return [
                'id'               => $p->id,
                'sku'              => $p->sku,
                'name'             => $p->name,
                'category_name'    => $p->category?->name ?? 'Uncategorized',
                'current_stock'    => (int) $p->current_stock,
                'minimum_stock'    => (int) $p->minimum_stock,
                'unit'             => $p->unit,
                'unit_price'       => (float) $p->unit_price,
                'next_forecast'    => (int) round($forecast['future_forecasts'][0] ?? 0),
                'suggested_quantity' => $rec['suggested_quantity'],
                'status'           => $rec['status'],
                'status_label'     => $rec['status_label'],
                'mape'             => $forecast['mape'],
                'rmse'             => $forecast['rmse'],
            ];
        })
        ->sortByDesc(fn ($item) => $item['status'] === 'RESTOCK_URGENT' ? 2 : ($item['suggested_quantity'] > 0 ? 1 : 0))
        ->values();

        return Inertia::render('Dashboard', [
            'stats'              => $stats,
            'criticalProducts'   => $criticalProducts,
            'recentTransactions' => $recentTransactions,
            'recentLogs'         => $recentLogs,
            'mutationTrends'     => $mutationTrends,
            'userStats'          => $userStats,

            // data analitik DES untuk Manajer Operasional
            'products'           => $products,
            'selectedProductId'  => $selectedProductId,
            'forecastData'       => $forecastData,
            'restockAlerts'      => $restockAlerts,
            'filters'            => [
                'alpha' => $request->query('alpha'),
                'beta'  => $request->query('beta'),
            ],
        ]);
    }
}
