<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderManagementController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'string', 'in:all,pending,confirmed,processing,shipped,delivered,cancelled'],
            'payment_status' => ['sometimes', 'string', 'in:all,pending,paid,failed,canceled'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = Order::query()
            ->with(['items', 'shippingAddress', 'billingAddress', 'payment', 'user'])
            ->when($validated['q'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('number', 'ilike', "%{$search}%")
                        ->orWhere('promo_code', 'ilike', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'ilike', "%{$search}%")->orWhere('name', 'ilike', "%{$search}%"));
                });
            })
            ->when(($validated['status'] ?? 'all') !== 'all', fn ($query) => $query->where('status', $validated['status']))
            ->when(($validated['payment_status'] ?? 'all') !== 'all', fn ($query) => $query->where('payment_status', $validated['payment_status']))
            ->latest()
            ->paginate($validated['per_page'] ?? 50);

        return OrderResource::collection($orders);
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return OrderResource::make($order->load(['items', 'shippingAddress', 'billingAddress', 'payment', 'shipment', 'user']));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        Gate::authorize('update', $order);

        $order->update(['status' => $request->validated('status')]);

        return OrderResource::make($order->fresh(['items', 'shippingAddress', 'billingAddress', 'payment', 'user']));
    }
}
