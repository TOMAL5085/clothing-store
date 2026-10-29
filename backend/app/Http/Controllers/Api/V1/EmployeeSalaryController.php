<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeSalaryResource;
use App\Models\EmployeeSalary;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeSalaryController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'month' => ['sometimes', 'string', 'size:7'],
            'status' => ['sometimes', 'string', Rule::in(EmployeeSalary::STATUSES)],
        ]);

        return EmployeeSalaryResource::collection(
            EmployeeSalary::query()->with('user')
                ->when($validated['month'] ?? null, fn ($query) => $query->where('month', $validated['month']))
                ->when($validated['status'] ?? null, fn ($query) => $query->where('status', $validated['status']))
                ->orderBy('month', 'desc')
                ->orderBy('id')
                ->get()
        );
    }

    public function show(EmployeeSalary $salary): EmployeeSalaryResource
    {
        return EmployeeSalaryResource::make($salary->load('user'));
    }
}
