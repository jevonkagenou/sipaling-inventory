<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'total_estimated_cost' => 'decimal:2',
        ];
    }

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

    /**
     * Rincian produk multi-item dalam berkas pengadaan
     */
    public function details(): HasMany
    {
        return $this->hasMany(RestockRequestDetail::class, 'restock_request_id');
    }

    /**
     * Scope kueri untuk memfilter berkas berstatus pending
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope kueri untuk memfilter berkas berstatus approved
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope kueri untuk memfilter berkas berstatus rejected
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Periksa apakah pengajuan masih berstatus pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Periksa apakah pengajuan telah disetujui Komisaris
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Periksa apakah pengajuan ditolak Komisaris
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
