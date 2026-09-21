<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index()
    {
        $dataBarang = [
            ['id' => 'ITM-001', 'nama' => 'Laptop ThinkPad T14', 'stok' => 45, 'status' => 'Aman'],
            ['id' => 'ITM-002', 'nama' => 'Monitor Dell 24 Inch', 'stok' => 12, 'status' => 'Reorder'],
            ['id' => 'ITM-003', 'nama' => 'Mouse Wireless Logitech', 'stok' => 150, 'status' => 'Aman'],
        ];

        return Inertia::render('Inventory/Index', [
            'barang' => $dataBarang
        ]);
    }
}
