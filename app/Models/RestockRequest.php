<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestockRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'request_no',
        'requested_by',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'total_estimated_cost',
        'general_notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'total_estimated_cost' => 'decimal:2',
    ];

    /**
     * Pengaju berkas pengadaan (Manajer Operasional)
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Peninjau berkas pengadaan (Komisaris)
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
