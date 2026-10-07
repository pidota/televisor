<?php

namespace App\Policies;

use App\Models\UrgentMessage;
use App\Models\User;

class UrgentMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UrgentMessage $urgentMessage): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'operator']);
    }

    public function update(User $user, UrgentMessage $urgentMessage): bool
    {
        return $user->hasAnyRole(['admin', 'operator']);
    }

    public function delete(User $user, UrgentMessage $urgentMessage): bool
    {
        return $user->hasRole('admin');
    }
}
