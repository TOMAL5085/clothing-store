<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Account $account): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Account $account): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Account $account): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Account $account): bool
    {
        return $user->isAdmin();
    }
}
