<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Expense $expense): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Expense $expense): bool
    {
        return $user->isAdmin();
    }
}
