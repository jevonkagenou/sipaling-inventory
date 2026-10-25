<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestockRequestDetail extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'restock_request_id',
        'product_id',
        'current_stock',
        'safety_stock',
        'suggested_quantity',
        'requested_quantity',
        'approved_quantity',
        'estimated_unit_price',
        'subtotal',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_stock' => 'integer',
            'safety_stock' => 'integer',
            'suggested_quantity' => 'integer',
            'requested_quantity' => 'integer',
            'approved_quantity' => 'integer',
            'estimated_unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Induk dokumen pengajuan restock (Header)
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(RestockRequest::class, 'restock_request_id');
    }

    /**
     * Data produk yang diajukan untuk restock
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
