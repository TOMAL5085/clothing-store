<?php

namespace App\Policies;

use App\Models\AccountTransaction;
use App\Models\User;

class AccountTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, AccountTransaction $transaction): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AccountTransaction $transaction): bool
    {
        return false;
    }

    public function delete(User $user, AccountTransaction $transaction): bool
    {
        return false;
    }

    public function restore(User $user, AccountTransaction $transaction): bool
    {
        return false;
    }

    public function forceDelete(User $user, AccountTransaction $transaction): bool
    {
        return false;
    }
}
