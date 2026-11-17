<?php

namespace App\Policies;

use App\Models\Rating;
use App\Models\User;

class RatingPolicy
{
    /** Pihak yang dinilai melaporkan pelanggaran; pemberi rating meminta koreksi. */
    public function report(User $user, Rating $rating): bool
    {
        return in_array((int) $user->id, [(int) $rating->ratee_id, (int) $rating->rater_id], true);
    }

    public function moderate(User $user, ?Rating $rating = null): bool
    {
        return $user->isAdmin();
    }
}
