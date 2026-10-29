<?php

namespace App\Policies;

use App\Models\ManualPayment;
use App\Models\User;

class ManualPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ManualPayment $payment): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ManualPayment $payment): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ManualPayment $payment): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, ManualPayment $payment): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, ManualPayment $payment): bool
    {
        return $user->isAdmin();
    }
}
