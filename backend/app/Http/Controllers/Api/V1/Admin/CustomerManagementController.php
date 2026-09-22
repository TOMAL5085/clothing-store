<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerManagementController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'string', 'in:all,active,inactive'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $customers = User::query()
            ->where('role', 'customer')
            ->withCount(['orders', 'addresses'])
            ->when($validated['q'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->orWhere('phone', 'ilike', "%{$search}%");
                });
            })
            ->when(($validated['status'] ?? 'all') !== 'all', fn ($query) => $query->where('status', $validated['status']))
            ->latest()
            ->paginate($validated['per_page'] ?? 50);

        return UserResource::collection($customers);
    }

    public function show(User $customer): UserResource
    {
        abort_unless($customer->role === 'customer', 404);

        return UserResource::make($customer->loadCount(['orders', 'addresses']));
    }

    public function updateStatus(Request $request, User $customer, AuditLogger $audit): JsonResponse
    {
        abort_unless($customer->role === 'customer', 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if ($customer->is($request->user())) {
            throw ValidationException::withMessages(['status' => 'You cannot change your own account status.']);
        }

        $previousStatus = $customer->status;
        $customer->update(['status' => $validated['status']]);

        $audit->log('customer.status_updated', $request->user(), $customer, [
            'customer_id' => $customer->id,
            'previous_status' => $previousStatus,
            'new_status' => $customer->status,
        ]);

        return response()->json(['data' => UserResource::make($customer->refresh()->loadCount(['orders', 'addresses']))]);
    }
}
