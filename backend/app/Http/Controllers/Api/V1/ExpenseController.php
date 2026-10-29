<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'category' => ['sometimes', 'string', 'max:80'],
            'status' => ['sometimes', 'string', Rule::in(Expense::STATUSES)],
        ]);

        return ExpenseResource::collection(
            Expense::query()->with('account')
                ->when($validated['category'] ?? null, fn ($query) => $query->where('category', $validated['category']))
                ->when($validated['status'] ?? null, fn ($query) => $query->where('status', $validated['status']))
                ->orderBy('expense_date', 'desc')
                ->orderBy('id')
                ->get()
        );
    }

    public function show(Expense $expense): ExpenseResource
    {
        return ExpenseResource::make($expense->load('account'));
    }
}
