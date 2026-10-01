<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForecastingLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'product_id',
        'period_date',
        'alpha',
        'beta',
        'actual_quantity',
        'forecast_quantity',
        'mape',
        'rmse',
    ];

    protected $casts = [
        'period_date' => 'date',
        'alpha' => 'float',
        'beta' => 'float',
        'actual_quantity' => 'integer',
        'forecast_quantity' => 'integer',
        'mape' => 'float',
        'rmse' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
