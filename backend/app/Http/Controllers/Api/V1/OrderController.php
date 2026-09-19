<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return OrderResource::collection($request->user()->orders()->with(['items', 'shippingAddress', 'payment'])->latest()->get());
    }

    public function show(Request $request, Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return OrderResource::make($order->load(['items', 'shippingAddress', 'payment']));
    }
}
