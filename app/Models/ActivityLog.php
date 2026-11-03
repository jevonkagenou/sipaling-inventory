<?php

namespace App\Models;

use RuntimeException;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class ActivityLog extends SpatieActivity
{
    protected $table = 'activity_log';

    /**
     * Mencegah modifikasi data audit log untuk menjamin sifat append-only permanen (anti-manipulasi).
     *
     * @throws \RuntimeException
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('Jejak audit bersifat append-only permanen dan tidak dapat dimodifikasi.');
    }

    /**
     * Mencegah penghapusan rekaman audit log untuk kepatuhan tata kelola.
     *
     * @throws \RuntimeException
     */
    public function delete(): ?bool
    {
        throw new RuntimeException('Jejak audit bersifat append-only permanen dan tidak dapat dihapus.');
    }
}
