<?php

namespace App\Policies;

use App\Models\EmployeeSalary;
use App\Models\User;

class EmployeeSalaryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, EmployeeSalary $salary): bool
    {
        return $user->isAdmin() || $user->id === $salary->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, EmployeeSalary $salary): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, EmployeeSalary $salary): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, EmployeeSalary $salary): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, EmployeeSalary $salary): bool
    {
        return $user->isAdmin();
    }
}
