<?php

namespace App\Http\Requests\Admin;

use App\Models\EmployeeSalary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeSalaryRequest extends FormRequest
{
    public function rules(): array
    {
        $salary = $this->route('employeeSalary');

        return [
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'reference_number' => ['sometimes', 'string', 'max:40', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', Rule::unique('employee_salaries', 'reference_number')->ignore($salary?->id)],
            'month' => ['sometimes', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/', Rule::unique('employee_salaries', 'month')->where(fn ($query) => $query->where('user_id', $this->input('user_id', $salary?->user_id)))->ignore($salary?->id)],
            'base_salary' => ['sometimes', 'numeric', 'min:0'],
            'allowances' => ['sometimes', 'numeric', 'min:0'],
            'deductions' => ['sometimes', 'numeric', 'min:0'],
            'net_salary' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in(EmployeeSalary::STATUSES)],
            'payment_date' => ['sometimes', 'date'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
