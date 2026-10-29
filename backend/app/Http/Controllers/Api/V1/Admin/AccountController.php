<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccountRequest;
use App\Http\Requests\Admin\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\Order;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'type' => ['sometimes', 'string', Rule::in(Account::TYPES)],
            'is_active' => ['sometimes', 'boolean'],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $accounts = Account::query()
            ->when($validated['type'] ?? null, fn ($query) => $query->where('type', $validated['type']))
            ->when($validated['is_active'] ?? null !== null, fn ($query) => $query->where('is_active', $validated['is_active']))
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search) {
                $query->where('code', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 50);

        return AccountResource::collection($accounts);
    }

    public function store(StoreAccountRequest $request, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $account = Account::query()->create([
            ...$request->validated(),
        ]);

        $audit->log('account.created', $request->user(), $account, [
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->type,
        ]);

        return response()->json(['data' => AccountResource::make($account)], 201);
    }

    public function show(Account $account): AccountResource
    {
        Gate::authorize('viewAny', Order::class);

        return AccountResource::make($account->load('transactions'));
    }

    public function update(UpdateAccountRequest $request, Account $account, AuditLogger $audit): AccountResource
    {
        Gate::authorize('viewAny', Order::class);

        $account->update([
            ...$request->validated(),
        ]);

        $audit->log('account.updated', $request->user(), $account->refresh(), [
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->type,
        ]);

        return AccountResource::make($account->refresh());
    }

    public function destroy(Account $account, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $code = $account->code;
        $account->delete();

        $audit->log('account.deleted', request()->user(), null, ['code' => $code]);

        return response()->json(['message' => 'Account deleted.']);
    }
}
