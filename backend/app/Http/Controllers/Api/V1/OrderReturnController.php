<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreReturnRequest;
use App\Http\Resources\ReturnRequestResource;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\OrderResolutionService;
use Illuminate\Support\Facades\Gate;

class OrderReturnController extends Controller
{
    public function index(Order $order)
    {
        Gate::authorize('view', $order);

        return ReturnRequestResource::collection($order->returnRequests()->with(['items.orderItem', 'refund'])->latest()->get());
    }

    public function store(StoreReturnRequest $request, Order $order, OrderResolutionService $service): ReturnRequestResource
    {
        Gate::authorize('view', $order);

        $return = $service->createReturnRequest(
            $order,
            $request->user(),
            $request->validated('items'),
            $request->validated('reason'),
        );

        return ReturnRequestResource::make($return);
    }

    public function show(Order $order, ReturnRequest $returnRequest): ReturnRequestResource
    {
        Gate::authorize('view', $order);
        abort_unless($returnRequest->order_id === $order->id, 404);

        return ReturnRequestResource::make($returnRequest->load(['items.orderItem', 'refund']));
    }
}
