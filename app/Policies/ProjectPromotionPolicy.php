<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectPromotion;
use App\Models\User;

class ProjectPromotionPolicy
{
    /** Statistik & detail: hanya pemilik promosi, atau Admin. */
    public function view(User $user, ProjectPromotion $promotion): bool
    {
        return $user->isAdmin() || $this->owns($user, $promotion);
    }

    /** Talenta & Admin tidak membeli promosi. */
    public function create(User $user): bool
    {
        return $user->isPenyedia();
    }

    public function promoteProject(User $user, Project $project): bool
    {
        return $user->isPenyedia() && (int) $project->user_id === (int) $user->id;
    }

    public function pay(User $user, ProjectPromotion $promotion): bool
    {
        return $this->owns($user, $promotion) && $promotion->isPendingPayment();
    }

    /** Konfirmasi "Saya Sudah Bayar": pemilik, transaksi nyata yang belum pernah dikonfirmasi. */
    public function submitProof(User $user, ProjectPromotion $promotion): bool
    {
        $trx = $promotion->transaction;

        return $this->owns($user, $promotion) && $trx && ! $trx->is_simulation && $trx->submitted_at === null
            && in_array($trx->status, ['pending', 'expired'], true);
    }

    public function cancel(User $user, ProjectPromotion $promotion): bool
    {
        return $this->owns($user, $promotion) && $promotion->isPendingPayment();
    }

    /** Daftar seluruh promosi/transaksi: hanya role admin (bukan sekadar email @tandang.id). */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function stop(User $user, ProjectPromotion $promotion): bool
    {
        return $user->isAdmin();
    }

    private function owns(User $user, ProjectPromotion $promotion): bool
    {
        return $user->isPenyedia() && (int) $promotion->user_id === (int) $user->id;
    }
}
