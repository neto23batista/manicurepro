<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AuditLogger;

/**
 * Separate from UserObserver (profile sync) to avoid merge conflicts.
 */
class UserRoleAuditObserver
{
    public function updating(User $user): void
    {
        if (! $user->isDirty('role')) {
            return;
        }

        $from = $user->getOriginal('role');
        $to = $user->role;

        if ($from === $to) {
            return;
        }

        AuditLogger::log('user.role_changed', $user, [
            'from' => $from,
            'to'   => $to,
        ]);
    }
}
