<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeSalaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => ['id' => $this->user->id, 'name' => $this->user->name, 'email' => $this->user->email]),
            'reference_number' => $this->reference_number,
            'month' => $this->month,
            'base_salary' => (float) $this->base_salary,
            'allowances' => (float) $this->allowances,
            'deductions' => (float) $this->deductions,
            'net_salary' => (float) $this->net_salary,
            'status' => $this->status,
            'payment_date' => $this->payment_date?->toISOString(),
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
        ];
    }
}
