<?php

namespace App\Policies;

use App\Models\ProjectWorkCompletion;
use App\Models\User;

class ProjectWorkCompletionPolicy
{
    /** Hanya Talenta & Penyedia yang terlibat langsung. */
    public function participate(User $user, ProjectWorkCompletion $completion): bool
    {
        return $completion->isParticipant($user);
    }

    public function resolve(User $user, ProjectWorkCompletion $completion): bool
    {
        return $user->isAdmin();
    }
}
