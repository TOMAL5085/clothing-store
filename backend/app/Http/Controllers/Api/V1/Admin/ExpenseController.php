<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Http\Requests\Admin\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\Order;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'account_id' => ['sometimes', 'string', 'max:20'],
            'category' => ['sometimes', 'string', 'max:80'],
            'status' => ['sometimes', 'string', Rule::in(array_merge(['all'], Expense::STATUSES))],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $expenses = Expense::query()->with('account')
            ->when(array_key_exists('account_id', $validated), function ($query) use ($validated) {
                $value = $validated['account_id'];
                if ($value === 'null') {
                    $query->whereNull('account_id');
                } elseif ($value === 'not_null') {
                    $query->whereNotNull('account_id');
                } else {
                    $query->where('account_id', $value);
                }
            })
            ->when($validated['category'] ?? null, fn ($query) => $query->where('category', $validated['category']))
            ->when($validated['status'] ?? 'all' !== 'all', fn ($query) => $query->where('status', $validated['status']))
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search) {
                $query->where('reference_number', 'ilike', "%{$search}%")
                    ->orWhere('vendor', 'ilike', "%{$search}%");
            }))
            ->orderBy('expense_date', 'desc')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 50);

        return ExpenseResource::collection($expenses);
    }

    public function store(StoreExpenseRequest $request, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $expense = Expense::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $audit->log('expense.created', $request->user(), $expense, [
            'reference_number' => $expense->reference_number,
            'account_id' => $expense->account_id,
            'amount' => $expense->amount,
        ]);

        return response()->json(['data' => ExpenseResource::make($expense->load('account'))], 201);
    }

    public function show(Expense $expense): ExpenseResource
    {
        Gate::authorize('viewAny', Order::class);

        return ExpenseResource::make($expense->load('account'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense, AuditLogger $audit): ExpenseResource
    {
        Gate::authorize('viewAny', Order::class);

        $expense->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        $audit->log('expense.updated', $request->user(), $expense->refresh(), [
            'reference_number' => $expense->reference_number,
            'status' => $expense->status,
            'amount' => $expense->amount,
        ]);

        return ExpenseResource::make($expense->refresh()->load('account'));
    }

    public function destroy(Expense $expense, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $reference = $expense->reference_number;
        $expense->delete();

        $audit->log('expense.deleted', request()->user(), null, ['reference_number' => $reference]);

        return response()->json(['message' => 'Expense deleted.']);
    }
}
