<?php

namespace App\Policies;

use App\Models\MediaAsset;
use App\Models\User;

class MediaAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MediaAsset $mediaAsset): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'operator']);
    }

    public function update(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->hasAnyRole(['admin', 'operator']);
    }

    public function delete(User $user, MediaAsset $mediaAsset): bool
    {
        return $user->hasAnyRole(['admin', 'operator']);
    }
}
