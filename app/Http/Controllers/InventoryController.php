<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\StockTransactionDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    /**
     * Display a listing of the inventory products.
     */
    public function index(Request $request): Response
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

        return Inertia::render('Inventory/Index', [
            'products' => $products,
            'filters' => [
                'search' => $search ?? '',
            ],
            'stats' => [
                'total_products'     => Product::count(),
                'total_categories'   => Category::count(),
                'total_transactions' => StockTransaction::count(),
                'total_details'      => StockTransactionDetail::count(),
                'reorder_count'      => Product::whereColumn('current_stock', '<=', 'minimum_stock')->count(),
                'aman_count'         => Product::whereColumn('current_stock', '>', 'minimum_stock')->count(),
            ],
        ]);
    }
}
