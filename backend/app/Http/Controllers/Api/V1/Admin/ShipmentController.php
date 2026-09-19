<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateShipmentRequest;
use App\Http\Requests\Admin\UpdateShipmentRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ShipmentResource;
use App\Models\Order;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ShipmentController extends Controller
{
    public function __construct(
        private readonly ShippingService $shipping,
    ) {
    }

    public function store(CreateShipmentRequest $request, Order $order): ShipmentResource
    {
        Gate::authorize('viewAny', Order::class);

        if ($order->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'payment_status' => 'Cannot create shipment for unpaid order.',
            ]);
        }

        $shipment = $this->shipping->createShipment($order, $request->validated());

        return ShipmentResource::make($shipment);
    }

    public function show(Order $order): ShipmentResource
    {
        Gate::authorize('viewAny', Order::class);

        $shipment = $order->shipment;
        if (! $shipment) {
            return response()->json(['message' => 'No shipment found for this order.'], 404);
        }

        return ShipmentResource::make($shipment);
    }

    public function update(UpdateShipmentRequest $request, Order $order): ShipmentResource
    {
        Gate::authorize('update', $order);

        $shipment = $order->shipment;
        if (! $shipment) {
            return response()->json(['message' => 'No shipment found for this order.'], 404);
        }

        $shipment = $this->shipping->updateShipment($shipment, $request->validated());

        // Sync order status with shipment status for shipped/delivered
        if (in_array($shipment->status, ['shipped', 'delivered'], true)) {
            $order->update(['status' => $shipment->status]);
        }

        return ShipmentResource::make($shipment->refresh());
    }
}