<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class RestockController extends Controller
{
    /**
     * Tampilkan Halaman Monitoring & Pengajuan Draf Restock
     */
    public function index(): Response
    {
        return Inertia::render('Restock/Index', [
            'requests' => [],
        ]);
    }

    /**
     * Tampilkan Halaman Otorisasi Persetujuan Khusus (Komisaris Only)
     */
    public function approval(): Response
    {
        return Inertia::render('Restock/Approval');
    }
}
