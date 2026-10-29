<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Zone;

class ZonePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Zone $zone): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Zone $zone): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Zone $zone): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Zone $zone): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Zone $zone): bool
    {
        return $user->isAdmin();
    }
}
