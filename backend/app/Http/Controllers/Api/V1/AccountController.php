<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'string', Rule::in(Account::TYPES)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return AccountResource::collection(
            Account::query()
                ->when($validated['type'] ?? null, fn ($query) => $query->where('type', $validated['type']))
                ->when($validated['is_active'] ?? null !== null, fn ($query) => $query->where('is_active', $validated['is_active']))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        );
    }

    public function show(Account $account): AccountResource
    {
        if (! $account->is_active) {
            abort(404);
        }

        return AccountResource::make($account->load('transactions'));
    }
}
