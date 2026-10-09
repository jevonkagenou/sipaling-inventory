<?php

namespace App\Services;

use App\Models\ForecastingLog;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoubleExponentialSmoothingService
{
    /**
     * Hitung Double Exponential Smoothing (Holt's Linear Trend Model)
     *
     * @param  array<float|int>  $series  Nilai aktual time-series [y1, y2, ..., yn]
     * @param  float  $alpha  Parameter pemulusan level (0 < alpha < 1)
     * @param  float  $beta  Parameter pemulusan tren (0 < beta < 1)
     * @param  int  $horizon  Jumlah periode ke depan yang diproyeksikan (default: 1)
     * @return array{
     *     levels: float[],
     *     trends: float[],
     *     fitted: (float|null)[],
     *     forecasts: float[],
     *     mape: float|null,
     *     rmse: float|null,
     *     mae: float|null,
     *     alpha: float,
     *     beta: float
     * }
     */
    public function compute(array $series, float $alpha = 0.2, float $beta = 0.1, int $horizon = 1): array
    {
        $n = count($series);

        if ($n === 0) {
            return [
                'levels' => [],
                'trends' => [],
                'fitted' => [],
                'forecasts' => array_fill(0, $horizon, 0.0),
                'mape' => null,
                'rmse' => null,
                'mae' => null,
                'alpha' => $alpha,
                'beta' => $beta,
            ];
        }

        // Re-index array dari 0 sampai n-1
        $y = array_values($series);

        $levels = array_fill(0, $n, 0.0);
        $trends = array_fill(0, $n, 0.0);
        $fitted = array_fill(0, $n, null);

        // Inisialisasi Periode Pertama (t = 0)
        $levels[0] = (float) $y[0];
        $trends[0] = $n > 1 ? (float) ($y[1] - $y[0]) : 0.0;
        $fitted[0] = (float) $y[0]; // Inisialisasi awal

        // Iterasi Pemulusan untuk t = 1 sampai n - 1
        for ($t = 1; $t < $n; $t++) {
            // Prediksi satu langkah ke depan (in-sample one-step ahead)
            $fitted[$t] = $levels[$t - 1] + $trends[$t - 1];

            // Level: Lt = alpha * Yt + (1 - alpha) * (Lt-1 + Tt-1)
            $levels[$t] = ($alpha * $y[$t]) + ((1.0 - $alpha) * ($levels[$t - 1] + $trends[$t - 1]));

            // Trend: Tt = beta * (Lt - Lt-1) + (1 - beta) * Tt-1
            $trends[$t] = ($beta * ($levels[$t] - $levels[$t - 1])) + ((1.0 - $beta) * $trends[$t - 1]);
        }

        // Proyeksi Masa Depan (m = 1 sampai horizon)
        // Y_hat(n + m) = Ln + m * Tn
        $forecasts = [];
        $lastLevel = $levels[$n - 1];
        $lastTrend = $trends[$n - 1];

        for ($m = 1; $m <= $horizon; $m++) {
            $projection = $lastLevel + ($m * $lastTrend);
            // Kuantitas barang fisik tidak boleh bernilai negatif
            $forecasts[] = max(0.0, round($projection, 2));
        }

        // Evaluasi Error (dimulai dari t = 1 hingga n - 1)
        $errorMetrics = $this->calculateErrors($y, $fitted);

        return [
            'levels' => array_map(fn ($v) => round($v, 4), $levels),
            'trends' => array_map(fn ($v) => round($v, 4), $trends),
            'fitted' => array_map(fn ($v) => $v !== null ? round($v, 2) : null, $fitted),
            'forecasts' => $forecasts,
            'mape' => $errorMetrics['mape'],
            'rmse' => $errorMetrics['rmse'],
            'mae' => $errorMetrics['mae'],
            'alpha' => round($alpha, 3),
            'beta' => round($beta, 3),
        ];
    }

    /**
     * Hitung Metrik Akurasi Error (RMSE, MAPE, MAE)
     *
     * @param  array<float|int>  $actuals
     * @param  array<float|null>  $fitted
     * @return array{mape: float|null, rmse: float|null, mae: float|null}
     */
    public function calculateErrors(array $actuals, array $fitted): array
    {
        $n = count($actuals);

        if ($n < 2) {
            return [
                'mape' => 0.0,
                'rmse' => 0.0,
                'mae' => 0.0,
            ];
        }

        $sumSquaredError = 0.0;
        $sumAbsError = 0.0;
        $sumPercentageError = 0.0;
        $validPercentageCount = 0;
        $count = 0;

        // Evaluasi dimulai dari indeks 1 (t = 2) di mana fitted value dihitung dari level dan tren t-1
        for ($t = 1; $t < $n; $t++) {
            if ($fitted[$t] === null) {
                continue;
            }

            $actual = (float) $actuals[$t];
            $predicted = (float) $fitted[$t];
            $error = $actual - $predicted;

            $sumSquaredError += ($error * $error);
            $sumAbsError += abs($error);
            $count++;

            // Hindari division by zero jika aktual = 0
            if (abs($actual) > 0.0001) {
                $sumPercentageError += abs($error / $actual);
                $validPercentageCount++;
            }
        }

        if ($count === 0) {
            return [
                'mape' => null,
                'rmse' => null,
                'mae' => null,
            ];
        }

        $rmse = sqrt($sumSquaredError / $count);
        $mae = $sumAbsError / $count;
        $mape = $validPercentageCount > 0
            ? ($sumPercentageError / $validPercentageCount) * 100.0
            : null;

        return [
            'mape' => $mape !== null ? round($mape, 2) : null,
            'rmse' => round($rmse, 2),
            'mae' => round($mae, 2),
        ];
    }

    /**
     * Grid Search Optimasi Parameter Alpha dan Beta (0.1 s.d. 0.9)
     * Mencari pasangan alpha-beta yang meminimalkan nilai MAPE (atau RMSE sebagai fallback).
     *
     * @param  array<float|int>  $series
     * @param  float  $step  Besaran langkah inkremental (default: 0.1)
     * @return array{
     *     optimal_alpha: float,
     *     optimal_beta: float,
     *     best_mape: float|null,
     *     best_rmse: float,
     *     result: array
     * }
     */
    public function optimizeGridSearch(array $series, float $step = 0.1, int $horizon = 1): array
    {
        $bestResult = null;
        $bestAlpha = 0.2;
        $bestBeta = 0.1;
        $lowestError = INF;

        // Iterasi alpha dari 0.1 sampai 0.9
        for ($a = 0.1; $a <= 0.95; $a += $step) {
            $alpha = round($a, 2);

            // Iterasi beta dari 0.1 sampai 0.9
            for ($b = 0.1; $b <= 0.95; $b += $step) {
                $beta = round($b, 2);

                $res = $this->compute($series, $alpha, $beta, $horizon);

                // Prioritaskan MAPE jika valid, jika null gunakan RMSE
                $comparableError = $res['mape'] ?? $res['rmse'] ?? INF;

                if ($comparableError < $lowestError) {
                    $lowestError = $comparableError;
                    $bestAlpha = $alpha;
                    $bestBeta = $beta;
                    $bestResult = $res;
                }
            }
        }

        // Fallback jika series sangat pendek
        if ($bestResult === null) {
            $bestResult = $this->compute($series, $bestAlpha, $bestBeta, $horizon);
        }

        return [
            'optimal_alpha' => $bestAlpha,
            'optimal_beta' => $bestBeta,
            'best_mape' => $bestResult['mape'],
            'best_rmse' => $bestResult['rmse'] ?? 0.0,
            'result' => $bestResult,
        ];
    }

    /**
     * Hitung Rekomendasi Kuantitas Restock
     * Formula: Max(0, Forecast + Safety Stock - Current Stock)
     *
     * @param  float|int  $forecastQuantity  Kuantitas perkiraan kebutuhan periode mendatang
     * @param  int  $safetyStock  Ambang batas persediaan pengaman (minimum_stock)
     * @param  int  $currentStock  Kuantitas persediaan fisik saat ini (current_stock)
     * @return array{
     *     suggested_quantity: int,
     *     status: 'RESTOCK_URGENT'|'RESTOCK_SUGGESTED'|'STOCK_ADEQUATE',
     *     status_label: string,
     *     reorder_point: int,
     *     deficit: int
     * }
     */
    public function calculateRestockRecommendation(
        float|int $forecastQuantity,
        int $safetyStock,
        int $currentStock
    ): array {
        $forecastInt = (int) ceil(max(0, $forecastQuantity));
        $reorderPoint = $safetyStock;

        // Formula usulan kuantitas restock
        $rawSuggested = $forecastInt + $safetyStock - $currentStock;
        $suggestedQuantity = max(0, $rawSuggested);

        if ($currentStock <= $safetyStock) {
            $status = 'RESTOCK_URGENT';
            $statusLabel = 'Kritis (Perlu Pengadaan Cepat)';
        } elseif ($suggestedQuantity > 0) {
            $status = 'RESTOCK_SUGGESTED';
            $statusLabel = 'Direkomendasikan Restock';
        } else {
            $status = 'STOCK_ADEQUATE';
            $statusLabel = 'Stok Aman';
        }

        return [
            'suggested_quantity' => $suggestedQuantity,
            'status' => $status,
            'status_label' => $statusLabel,
            'reorder_point' => $reorderPoint,
            'deficit' => max(0, $safetyStock - $currentStock),
        ];
    }

    /**
     * Mendapatkan daftar periode bulanan yang sudah tutup buku (closed accounting periods).
     * Secara otomatis mengecualikan:
     * 1. Bulan inisiasi awal jika volume transaksi sangat kecil (anomali parsial < 10 transaksi).
     * 2. Bulan berjalan/terakhir jika belum tutup buku (misal: data terpotong sebelum tanggal 25).
     *
     * @return array<string>
     */
    public function getClosedMonthlyPeriods(): array
    {
        $systemMonthly = DB::table('stock_transactions')
            ->where('type', 'outbound')
            ->select(
                DB::raw("DATE_FORMAT(transaction_date, '%Y-%m') as period"),
                DB::raw('COUNT(*) as tx_count'),
                DB::raw('MAX(transaction_date) as max_date')
            )
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        if ($systemMonthly->isEmpty()) {
            return [];
        }

        $validPeriods = [];
        $totalPeriods = $systemMonthly->count();

        foreach ($systemMonthly as $index => $row) {
            $isFirst = ($index === 0);
            $isLast = ($index === $totalPeriods - 1);

            // 1. Abaikan bulan inisiasi parsial (misal hanya ada 1 transaksi terisolasi)
            if ($isFirst && $row->tx_count < 10 && $totalPeriods > 2) {
                continue;
            }

            // 2. Abaikan bulan terakhir jika data terpotong sebelum tanggal 25 (unclosed month)
            if ($isLast && $totalPeriods > 2) {
                $maxDay = (int) Carbon::parse($row->max_date)->format('d');
                if ($maxDay < 25) {
                    continue;
                }
            }

            $validPeriods[] = $row->period;
        }

        return $validPeriods;
    }

    /**
     * Ekstraksi Agregasi Time-Series Bulanan untuk Produk dari Riwayat Transaksi Outbound
     *
     * @param  bool  $onlyClosedPeriods  Batasi hanya pada periode yang sudah tutup buku (default: true)
     * @return Collection<int, object{period: string, total_quantity: int}>
     */
    public function getMonthlyOutboundSeries(string $productId, bool $onlyClosedPeriods = true): Collection
    {
        $query = DB::table('stock_transaction_details')
            ->join('stock_transactions', 'stock_transaction_details.stock_transaction_id', '=', 'stock_transactions.id')
            ->where('stock_transaction_details.product_id', $productId)
            ->where('stock_transactions.type', 'outbound')
            ->select(
                DB::raw("DATE_FORMAT(stock_transactions.transaction_date, '%Y-%m') as period"),
                DB::raw('SUM(stock_transaction_details.quantity) as total_quantity')
            )
            ->groupBy('period')
            ->orderBy('period', 'asc');

        if ($onlyClosedPeriods) {
            $closed = $this->getClosedMonthlyPeriods();
            if (! empty($closed)) {
                $query->whereIn(DB::raw("DATE_FORMAT(stock_transactions.transaction_date, '%Y-%m')"), $closed);
            }
        }

        return $query->get();
    }

    /**
     * Jalankan Peramalan Menyeluruh untuk Produk Tertentu
     * Menarik time-series, mencari parameter optimal via Grid Search, menghitung restock, dan menyusun payload dasbor.
     */
    public function forecastProduct(
        Product $product,
        ?float $manualAlpha = null,
        ?float $manualBeta = null,
        int $horizon = 3
    ): array {
        $monthlyData = $this->getMonthlyOutboundSeries($product->id);
        $periods = $monthlyData->pluck('period')->toArray();
        $quantities = $monthlyData->pluck('total_quantity')->map(fn ($q) => (float) $q)->toArray();

        // Jika data historis kosong atau kurang dari 2 periode tutup buku, gunakan fallback safety stock
        if (count($quantities) < 2) {
            $currentStock = (int) $product->current_stock;
            $minimumStock = (int) $product->minimum_stock;
            $fallbackRestock = $this->calculateRestockRecommendation(0, $minimumStock, $currentStock);

            return [
                'product' => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'current_stock' => $currentStock,
                    'minimum_stock' => $minimumStock,
                    'unit' => $product->unit,
                ],
                'historical_periods' => $periods,
                'actual_series' => $quantities,
                'fitted_series' => [],
                'future_periods' => [],
                'future_forecasts' => array_fill(0, $horizon, 0.0),
                'optimal_alpha' => 0.2,
                'optimal_beta' => 0.1,
                'mape' => null,
                'accuracy_rate' => null,
                'rmse' => null,
                'mae' => null,
                'restock_recommendation' => $fallbackRestock,
                'has_sufficient_data' => false,
            ];
        }

        // Tentukan Alpha & Beta: Gunakan parameter manual jika diinputkan, atau jalankan Grid Search
        if ($manualAlpha !== null && $manualBeta !== null) {
            $computation = $this->compute($quantities, $manualAlpha, $manualBeta, $horizon);
            $optimalAlpha = $manualAlpha;
            $optimalBeta = $manualBeta;
        } else {
            $grid = $this->optimizeGridSearch($quantities, 0.1, $horizon);
            $computation = $grid['result'];
            $optimalAlpha = $grid['optimal_alpha'];
            $optimalBeta = $grid['optimal_beta'];
        }

        // Susun Label Periode Masa Depan (Contoh: YYYY-MM berikutnya)
        $futurePeriods = [];
        $lastPeriodStr = end($periods);
        if ($lastPeriodStr) {
            $lastCarbon = Carbon::createFromFormat('Y-m', $lastPeriodStr);
            for ($i = 1; $i <= $horizon; $i++) {
                $futurePeriods[] = $lastCarbon->copy()->addMonths($i)->format('Y-m');
            }
        }

        // Hitung Rekomendasi Restock untuk Periode Pertama (+1)
        $nextPeriodDemand = $computation['forecasts'][0] ?? 0.0;
        $restockRec = $this->calculateRestockRecommendation(
            $nextPeriodDemand,
            (int) $product->minimum_stock,
            (int) $product->current_stock
        );

        return [
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'current_stock' => (int) $product->current_stock,
                'minimum_stock' => (int) $product->minimum_stock,
                'unit' => $product->unit,
            ],
            'historical_periods' => $periods,
            'actual_series' => $quantities,
            'fitted_series' => $computation['fitted'],
            'future_periods' => $futurePeriods,
            'future_forecasts' => $computation['forecasts'],
            'optimal_alpha' => $optimalAlpha,
            'optimal_beta' => $optimalBeta,
            'mape' => $computation['mape'],
            'accuracy_rate' => $computation['mape'] !== null ? max(0.0, round(100.0 - $computation['mape'], 1)) : null,
            'rmse' => $computation['rmse'],
            'mae' => $computation['mae'],
            'restock_recommendation' => $restockRec,
            'has_sufficient_data' => true,
        ];
    }

    /**
     * Catat Hasil Peramalan ke Tabel forecasting_logs
     *
     * @param  array  $forecastResult  Hasil dari forecastProduct()
     */
    public function persistForecastLog(Product $product, array $forecastResult): ForecastingLog
    {
        $targetPeriod = $forecastResult['future_periods'][0] ?? now()->addMonth()->format('Y-m');
        $periodDate = Carbon::createFromFormat('Y-m', $targetPeriod)->startOfMonth()->toDateString();
        $forecastQty = (int) round($forecastResult['future_forecasts'][0] ?? 0);

        return ForecastingLog::updateOrCreate(
            [
                'product_id' => $product->id,
                'period_date' => $periodDate,
            ],
            [
                'alpha' => $forecastResult['optimal_alpha'],
                'beta' => $forecastResult['optimal_beta'],
                'actual_quantity' => null, // Belum terealisasi
                'forecast_quantity' => $forecastQty,
                'mape' => $forecastResult['mape'],
                'rmse' => $forecastResult['rmse'],
            ]
        );
    }
}
