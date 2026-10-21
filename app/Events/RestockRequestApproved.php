<?php

namespace App\Events;

use App\Models\RestockRequest;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RestockRequestApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Inisiasi event persetujuan dokumen pengadaan restock.
     *
     * @param  array<array{product_id: string, quantity: int, unit_price?: float|null, notes?: string|null}>  $items
     */
    public function __construct(
        public RestockRequest $request,
        public User $reviewer,
        public bool $autoGenerateInbound = false,
        public array $items = [],
        public array $meta = []
    ) {}
}
