<?php

namespace Tests\Unit;

use App\Services\DoubleExponentialSmoothingService;
use PHPUnit\Framework\TestCase;

class DoubleExponentialSmoothingServiceTest extends TestCase
{
    protected DoubleExponentialSmoothingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DoubleExponentialSmoothingService;
    }

    public function test_it_handles_empty_series_gracefully(): void
    {
        $result = $this->service->compute([], 0.2, 0.1, 3);

        $this->assertEmpty($result['levels']);
        $this->assertEmpty($result['trends']);
        $this->assertEmpty($result['fitted']);
        $this->assertCount(3, $result['forecasts']);
        $this->assertSame([0.0, 0.0, 0.0], $result['forecasts']);
        $this->assertNull($result['mape']);
        $this->assertNull($result['rmse']);
    }

    public function test_it_handles_single_value_series(): void
    {
        $result = $this->service->compute([100], 0.2, 0.1, 2);

        $this->assertCount(1, $result['levels']);
        $this->assertEquals(100.0, $result['levels'][0]);
        $this->assertEquals(0.0, $result['trends'][0]);
        $this->assertCount(2, $result['forecasts']);
        $this->assertEquals(100.0, $result['forecasts'][0]);
    }

    public function test_it_calculates_holts_linear_smoothing_correctly(): void
    {
        // 5 periode data bulanan
        $series = [100, 120, 130, 150, 170];
        $alpha = 0.2;
        $beta = 0.1;

        $result = $this->service->compute($series, $alpha, $beta, 2);

        // t=0: L0 = 100, T0 = 120 - 100 = 20
        $this->assertEquals(100.0, $result['levels'][0]);
        $this->assertEquals(20.0, $result['trends'][0]);

        // t=1 (Y1=120):
        // Fitted[1] = L0 + T0 = 100 + 20 = 120
        // L1 = 0.2 * 120 + 0.8 * (100 + 20) = 24 + 96 = 120
        // T1 = 0.1 * (120 - 100) + 0.9 * 20 = 2 + 18 = 20
        $this->assertEquals(120.0, $result['levels'][1]);
        $this->assertEquals(20.0, $result['trends'][1]);
        $this->assertEquals(120.0, $result['fitted'][1]);

        // Proyeksi 2 periode ke depan:
        // Forecast m=1: L4 + 1 * T4
        // Forecast m=2: L4 + 2 * T4
        $this->assertCount(2, $result['forecasts']);
        $this->assertGreaterThan(170, $result['forecasts'][0]);
        $this->assertGreaterThan($result['forecasts'][0], $result['forecasts'][1]);
    }

    public function test_it_calculates_error_metrics_rmse_and_mape(): void
    {
        $actuals = [100, 110, 120, 130];
        $fitted = [null, 105, 125, 128]; // errors: 5, -5, 2

        $metrics = $this->service->calculateErrors($actuals, $fitted);

        // N = 3
        // sumSquared = 5^2 + (-5)^2 + 2^2 = 25 + 25 + 4 = 54
        // RMSE = sqrt(54 / 3) = sqrt(18) ≈ 4.24
        $this->assertEquals(4.24, $metrics['rmse']);

        // sumPercentage = (5/110) + (5/120) + (2/130) = 0.04545 + 0.04167 + 0.01538 = 0.1025
        // MAPE = (0.1025 / 3) * 100 ≈ 3.42%
        $this->assertNotNull($metrics['mape']);
        $this->assertGreaterThan(3.0, $metrics['mape']);
        $this->assertLessThan(4.0, $metrics['mape']);
    }

    public function test_it_optimizes_alpha_and_beta_using_grid_search(): void
    {
        $series = [50, 60, 72, 85, 99, 115, 132];

        $optimized = $this->service->optimizeGridSearch($series, 0.1, 1);

        $this->assertGreaterThanOrEqual(0.1, $optimized['optimal_alpha']);
        $this->assertLessThanOrEqual(0.95, $optimized['optimal_alpha']);
        $this->assertGreaterThanOrEqual(0.1, $optimized['optimal_beta']);
        $this->assertLessThanOrEqual(0.95, $optimized['optimal_beta']);

        $this->assertArrayHasKey('result', $optimized);
        $this->assertNotNull($optimized['best_rmse']);
        $this->assertNotEmpty($optimized['result']['forecasts']);
    }

    public function test_it_calculates_restock_recommendation_accurately(): void
    {
        // Kasus 1: Stok masih aman di atas batas restock
        // Forecast = 50, Safety Stock = 20, Current Stock = 100
        // Raw: 50 + 20 - 100 = -30 -> Max(0, -30) = 0
        $rec1 = $this->service->calculateRestockRecommendation(50, 20, 100);
        $this->assertSame(0, $rec1['suggested_quantity']);
        $this->assertSame('STOCK_ADEQUATE', $rec1['status']);

        // Kasus 2: Stok mendekati titik kritis dan diproyeksikan defisit
        // Forecast = 80, Safety Stock = 30, Current Stock = 40
        // Raw: 80 + 30 - 40 = 70
        $rec2 = $this->service->calculateRestockRecommendation(80, 30, 40);
        $this->assertSame(70, $rec2['suggested_quantity']);
        $this->assertSame('RESTOCK_SUGGESTED', $rec2['status']);

        // Kasus 3: Stok darurat menyentuh/di bawah safety stock
        // Forecast = 60, Safety Stock = 50, Current Stock = 20
        // Raw: 60 + 50 - 20 = 90
        $rec3 = $this->service->calculateRestockRecommendation(60, 50, 20);
        $this->assertSame(90, $rec3['suggested_quantity']);
        $this->assertSame('RESTOCK_URGENT', $rec3['status']);
        $this->assertSame(30, $rec3['deficit']); // 50 - 20 = 30
    }
}
