<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetOtp extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'password_reset_otps';

    protected $fillable = [
        'user_id',
        'otp',
        'expired_at',
        'is_used',
        'used_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'used_at' => 'datetime',
            'is_used' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expired_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->is_used && ! $this->isExpired();
    }
}
