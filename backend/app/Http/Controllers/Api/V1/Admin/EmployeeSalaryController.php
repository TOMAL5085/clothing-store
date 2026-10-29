<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmployeeSalaryRequest;
use App\Http\Requests\Admin\UpdateEmployeeSalaryRequest;
use App\Http\Resources\EmployeeSalaryResource;
use App\Models\EmployeeSalary;
use App\Models\Order;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EmployeeSalaryController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'user_id' => ['sometimes', 'string', 'max:20'],
            'month' => ['sometimes', 'string', 'size:7'],
            'status' => ['sometimes', 'string', Rule::in(array_merge(['all'], EmployeeSalary::STATUSES))],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $salaries = EmployeeSalary::query()->with('user')
            ->when(array_key_exists('user_id', $validated), function ($query) use ($validated) {
                $value = $validated['user_id'];
                if ($value === 'null') {
                    $query->whereNull('user_id');
                } elseif ($value === 'not_null') {
                    $query->whereNotNull('user_id');
                } else {
                    $query->where('user_id', $value);
                }
            })
            ->when($validated['month'] ?? null, fn ($query) => $query->where('month', $validated['month']))
            ->when($validated['status'] ?? 'all' !== 'all', fn ($query) => $query->where('status', $validated['status']))
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search) {
                $query->where('reference_number', 'ilike', "%{$search}%");
            }))
            ->orderBy('month', 'desc')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 50);

        return EmployeeSalaryResource::collection($salaries);
    }

    public function store(StoreEmployeeSalaryRequest $request, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $salary = EmployeeSalary::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $audit->log('salary.created', $request->user(), $salary, [
            'reference_number' => $salary->reference_number,
            'user_id' => $salary->user_id,
            'month' => $salary->month,
            'net_salary' => $salary->net_salary,
        ]);

        return response()->json(['data' => EmployeeSalaryResource::make($salary->load('user'))], 201);
    }

    public function show(EmployeeSalary $salary): EmployeeSalaryResource
    {
        Gate::authorize('viewAny', Order::class);

        return EmployeeSalaryResource::make($salary->load('user'));
    }

    public function update(UpdateEmployeeSalaryRequest $request, EmployeeSalary $salary, AuditLogger $audit): EmployeeSalaryResource
    {
        Gate::authorize('viewAny', Order::class);

        $salary->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        $audit->log('salary.updated', $request->user(), $salary->refresh(), [
            'reference_number' => $salary->reference_number,
            'status' => $salary->status,
            'net_salary' => $salary->net_salary,
        ]);

        return EmployeeSalaryResource::make($salary->refresh()->load('user'));
    }

    public function destroy(EmployeeSalary $salary, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $reference = $salary->reference_number;
        $salary->delete();

        $audit->log('salary.deleted', request()->user(), null, ['reference_number' => $reference]);

        return response()->json(['message' => 'Salary record deleted.']);
    }
}
