<?php

namespace App\Policies;

use App\Models\RestockRequest;
use App\Models\User;

class RestockRequestPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar berkas pengadaan restock.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('restock.request') || $user->can('restock.approve');
    }

    /**
     * Tentukan apakah pengguna dapat melihat rincian berkas pengadaan restock tertentu.
     */
    public function view(User $user, RestockRequest $restockRequest): bool
    {
        return $user->can('restock.request') || $user->can('restock.approve');
    }

    /**
     * Tentukan apakah pengguna dapat mengajukan usulan pengadaan restock baru.
     * Khusus: Manajer Operasional (izin: restock.request).
     */
    public function create(User $user): bool
    {
        return $user->hasRole('manajer-operasional') || $user->can('restock.request');
    }

    /**
     * Tentukan apakah pengguna dapat menyetujui berkas pengadaan restock.
     * Khusus: Komisaris (izin: restock.approve) dan berkas masih berstatus pending.
     */
    public function approve(User $user, RestockRequest $restockRequest): bool
    {
        return ($user->hasRole('komisaris') || $user->can('restock.approve'))
            && $restockRequest->isPending();
    }

    /**
     * Tentukan apakah pengguna dapat menolak berkas pengadaan restock.
     * Khusus: Komisaris (izin: restock.approve) dan berkas masih berstatus pending.
     */
    public function reject(User $user, RestockRequest $restockRequest): bool
    {
        return ($user->hasRole('komisaris') || $user->can('restock.approve'))
            && $restockRequest->isPending();
    }

    /**
     * Tentukan apakah pengguna dapat membatalkan/menghapus pengajuan restock miliknya.
     * Hanya pengaju awal (Manajer) selama berkas masih berstatus pending.
     */
    public function delete(User $user, RestockRequest $restockRequest): bool
    {
        return $user->id === $restockRequest->requested_by && $restockRequest->isPending();
    }
}
