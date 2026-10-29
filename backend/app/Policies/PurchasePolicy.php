<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Purchase $purchase): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Purchase $purchase): bool
    {
        return $user->isAdmin();
    }
}
