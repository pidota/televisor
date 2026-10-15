<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->hasRole('admin')) {
            return false;
        }

        if ($user->is($model)) {
            return false;
        }

        if ($model->hasRole('admin') && $this->adminCount() <= 1) {
            return false;
        }

        return true;
    }

    private function adminCount(): int
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->count();
    }
}
