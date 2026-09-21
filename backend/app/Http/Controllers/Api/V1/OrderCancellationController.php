<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreCancellationRequest;
use App\Http\Resources\CancellationRequestResource;
use App\Models\Order;
use App\Services\OrderResolutionService;
use Illuminate\Support\Facades\Gate;

class OrderCancellationController extends Controller
{
    public function store(StoreCancellationRequest $request, Order $order, OrderResolutionService $service): CancellationRequestResource
    {
        Gate::authorize('view', $order);

        $cancellation = $service->createCancellationRequest($order, $request->user(), $request->validated('reason'));

        return CancellationRequestResource::make($cancellation);
    }

    public function show(Order $order): CancellationRequestResource
    {
        Gate::authorize('view', $order);

        return CancellationRequestResource::make($order->cancellationRequest()->with('refund')->firstOrFail());
    }
}
