<?php

namespace App\Policies;

use App\Models\Screen;
use App\Models\User;

class ScreenPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Screen $screen): bool
    {
        return true;
    }

    public function pair(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'operator']);
    }

    public function update(User $user, Screen $screen): bool
    {
        return $user->hasAnyRole(['admin', 'operator']);
    }

    public function disable(User $user, Screen $screen): bool
    {
        return $user->hasAnyRole(['admin', 'operator']);
    }

    public function revoke(User $user, Screen $screen): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Screen $screen): bool
    {
        return $user->hasRole('admin');
    }
}
