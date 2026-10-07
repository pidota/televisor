<?php

namespace App\Policies;

use App\Models\ScreenGroup;
use App\Models\User;

class ScreenGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ScreenGroup $screenGroup): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'operator']);
    }

    public function update(User $user, ScreenGroup $screenGroup): bool
    {
        return $user->hasAnyRole(['admin', 'operator']);
    }

    public function delete(User $user, ScreenGroup $screenGroup): bool
    {
        return $user->hasAnyRole(['admin', 'operator']);
    }
}
