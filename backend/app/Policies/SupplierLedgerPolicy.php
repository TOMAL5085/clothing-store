<?php

namespace App\Policies;

use App\Models\SupplierLedger;
use App\Models\User;

class SupplierLedgerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, SupplierLedger $ledger): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, SupplierLedger $ledger): bool
    {
        return false;
    }

    public function delete(User $user, SupplierLedger $ledger): bool
    {
        return false;
    }

    public function restore(User $user, SupplierLedger $ledger): bool
    {
        return false;
    }

    public function forceDelete(User $user, SupplierLedger $ledger): bool
    {
        return false;
    }
}
