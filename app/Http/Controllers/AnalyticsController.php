<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Services\DoubleExponentialSmoothingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            ->get(['id', 'category_id', 'sku', 'name', 'unit', 'unit_price', 'current_stock', 'minimum_stock']);

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
                'unit_price' => (float) $p->unit_price,
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

    /**
     * Ekspor Rekomendasi Restock Pengadaan ke Berkas Excel (.xlsx) atau CSV
     */
    public function exportRestock(Request $request): StreamedResponse
    {
        $format = strtolower($request->query('format', 'xlsx'));

        $products = Product::query()
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'category_id', 'sku', 'name', 'unit', 'unit_price', 'current_stock', 'minimum_stock']);

        // Susun & urutkan data rekomendasi restock berdasarkan prioritas urgensi
        $restockItems = $products->map(function ($p) {
            $forecast = $this->desService->forecastProduct($p, horizon: 1);
            $rec = $forecast['restock_recommendation'];
            $suggestedQty = (int) ($rec['suggested_quantity'] ?? 0);
            $unitPrice = (float) $p->unit_price;

            return [
                'sku' => $p->sku,
                'name' => $p->name,
                'category' => $p->category?->name ?? 'Uncategorized',
                'unit' => $p->unit,
                'current_stock' => (int) $p->current_stock,
                'minimum_stock' => (int) $p->minimum_stock,
                'next_forecast' => (int) round($forecast['future_forecasts'][0] ?? 0),
                'suggested_quantity' => $suggestedQty,
                'unit_price' => $unitPrice,
                'estimated_total_cost' => $suggestedQty * $unitPrice,
                'status' => $rec['status'] ?? 'NORMAL',
                'status_label' => $rec['status_label'] ?? 'Stok Aman',
            ];
        })->sortByDesc(fn ($item) => $item['status'] === 'RESTOCK_URGENT' ? 2 : ($item['suggested_quantity'] > 0 ? 1 : 0))->values();

        // Rekam jejak audit ekspor berkas rekomendasi restock
        if (class_exists(ActivityLog::class)) {
            ActivityLog::create([
                'log_name' => 'inventory',
                'description' => 'Mengekspor data lembar kerja rekomendasi restock (DES) format '.strtoupper($format),
                'causer_type' => Auth::user() ? get_class(Auth::user()) : null,
                'causer_id' => Auth::id(),
                'event' => 'exported',
                'properties' => [
                    'format' => $format,
                    'total_items' => $restockItems->count(),
                    'urgent_count' => $restockItems->where('status', 'RESTOCK_URGENT')->count(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);
        }

        $dateStr = now()->format('Y-m-d');

        // Pilihan 1: Berkas CSV (Ringan, Standar RFC 4180 dengan UTF-8 BOM)
        if ($format === 'csv') {
            $filename = "rekomendasi_restock_sipaling_{$dateStr}.csv";
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($restockItems) {
                $handle = fopen('php://output', 'w');
                fputs($handle, "\xEF\xBB\xBF");

                fputcsv($handle, [
                    'No',
                    'SKU Produk',
                    'Nama Barang',
                    'Kategori',
                    'Satuan',
                    'Stok Fisik Aktual',
                    'Safety Stock (Minimum)',
                    'Prediksi Permintaan DES (Bulan Depan)',
                    'Saran Kuantitas Restock',
                    'Estimasi Harga Satuan (Rp)',
                    'Estimasi Total Biaya Restock (Rp)',
                    'Status Urgensi',
                ]);

                $no = 1;
                foreach ($restockItems as $item) {
                    fputcsv($handle, [
                        $no++,
                        $item['sku'],
                        $item['name'],
                        $item['category'],
                        $item['unit'],
                        $item['current_stock'],
                        $item['minimum_stock'],
                        $item['next_forecast'],
                        $item['suggested_quantity'],
                        number_format($item['unit_price'], 2, '.', ''),
                        number_format($item['estimated_total_cost'], 2, '.', ''),
                        $item['status_label'],
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        }

        // Pilihan 2: Microsoft Excel (.xlsx) dengan PhpSpreadsheet (Desain Palet SIPALING #2563EB)
        $filename = "rekomendasi_restock_sipaling_{$dateStr}.xlsx";
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekomendasi Restock');

        $columns = [
            'No',
            'SKU Produk',
            'Nama Barang',
            'Kategori',
            'Satuan',
            'Stok Fisik Aktual',
            'Safety Stock (Minimum)',
            'Prediksi Permintaan DES',
            'Saran Kuantitas Restock',
            'Estimasi Harga Satuan (Rp)',
            'Estimasi Total Biaya (Rp)',
            'Status Urgensi',
        ];

        $colLetters = range('A', 'L');
        foreach ($columns as $idx => $title) {
            $sheet->setCellValue("{$colLetters[$idx]}1", $title);
        }

        // Styling Header (Royal Blue #2563EB, Bold White text)
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Isi Data Baris
        $rowIdx = 2;
        foreach ($restockItems as $noIdx => $item) {
            $sheet->setCellValue("A{$rowIdx}", $noIdx + 1);
            $sheet->setCellValue("B{$rowIdx}", $item['sku']);
            $sheet->setCellValue("C{$rowIdx}", $item['name']);
            $sheet->setCellValue("D{$rowIdx}", $item['category']);
            $sheet->setCellValue("E{$rowIdx}", $item['unit']);
            $sheet->setCellValue("F{$rowIdx}", $item['current_stock']);
            $sheet->setCellValue("G{$rowIdx}", $item['minimum_stock']);
            $sheet->setCellValue("H{$rowIdx}", $item['next_forecast']);
            $sheet->setCellValue("I{$rowIdx}", $item['suggested_quantity']);
            $sheet->setCellValue("J{$rowIdx}", $item['unit_price']);
            $sheet->setCellValue("K{$rowIdx}", $item['estimated_total_cost']);
            $sheet->setCellValue("L{$rowIdx}", $item['status_label']);

            // Highlight warna status
            if ($item['status'] === 'RESTOCK_URGENT') {
                $sheet->getStyle("L{$rowIdx}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'D97706']],
                ]);
            } else {
                $sheet->getStyle("L{$rowIdx}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '059669']],
                ]);
            }

            // Zebra striping untuk kenyamanan membaca
            if ($rowIdx % 2 === 1) {
                $sheet->getStyle("A{$rowIdx}:L{$rowIdx}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC'],
                    ],
                ]);
            }

            $rowIdx++;
        }

        $lastRow = $rowIdx - 1;

        if ($lastRow >= 2) {
            $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F2:K{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("L2:L{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("F2:I{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("J2:K{$lastRow}")->getNumberFormat()->setFormatCode('"Rp" #,##0');

            $sheet->getStyle("A1:L{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
            ]);
        }

        foreach ($colLetters as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $callback = function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        };

        return response()->stream($callback, 200, $headers);
    }
}
