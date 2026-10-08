<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\StockTransactionDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Menampilkan dasbor utama dengan ringkasan metrik lintas modul.
     */
    public function index(Request $request): Response
    {
        $today = Carbon::today();

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

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'criticalProducts' => $criticalProducts,
            'recentTransactions' => $recentTransactions,
            'recentLogs' => $recentLogs,
        ]);
    }
}
