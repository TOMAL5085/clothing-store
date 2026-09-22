<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateShipmentRequest;
use App\Http\Requests\Admin\UpdateShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Order;
use App\Services\AuditLogger;
use App\Services\Couriers\CourierGateway;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ShipmentController extends Controller
{
    public function __construct(
        private readonly ShippingService $shipping,
        private readonly CourierGateway $courier,
    ) {}

    public function store(CreateShipmentRequest $request, Order $order, AuditLogger $audit): ShipmentResource
    {
        Gate::authorize('viewAny', Order::class);

        if ($order->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'payment_status' => 'Cannot create shipment for unpaid order.',
            ]);
        }

        $shipment = $this->shipping->createShipment($order, $request->validated());

        $audit->log('shipment.created', $request->user(), $shipment, [
            'order_number' => $order->number,
            'status' => $shipment->status,
            'carrier' => $shipment->carrier,
            'tracking_number' => $shipment->tracking_number,
        ]);

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

    public function update(UpdateShipmentRequest $request, Order $order, AuditLogger $audit): ShipmentResource
    {
        Gate::authorize('update', $order);

        $shipment = $order->shipment;
        if (! $shipment) {
            return response()->json(['message' => 'No shipment found for this order.'], 404);
        }

        $previousStatus = $shipment->status;
        $shipment = $this->shipping->updateShipment($shipment, $request->validated());

        // Sync order status with shipment status for shipped/delivered
        if (in_array($shipment->status, ['shipped', 'delivered'], true)) {
            $order->update(['status' => $shipment->status]);
        }

        $audit->log('shipment.updated', $request->user(), $shipment, [
            'order_number' => $order->number,
            'previous_status' => $previousStatus,
            'new_status' => $shipment->status,
        ]);

        return ShipmentResource::make($shipment->refresh());
    }

    public function status(Order $order): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $shipment = $order->shipment;
        if (! $shipment) {
            return response()->json(['message' => 'No shipment found for this order.'], 404);
        }

        $rawStatus = $this->courier->getStatus($shipment);
        $mappedStatus = $this->courier->mapExternalStatusToInternal($rawStatus);

        return response()->json([
            'status' => $mappedStatus,
        ]);
    }

    public function sync(Request $request, Order $order, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $shipment = $order->shipment;
        if (! $shipment) {
            return response()->json(['message' => 'No shipment found for this order.'], 404);
        }

        $previousStatus = $shipment->status;
        $this->shipping->syncShipmentStatus($shipment);

        $audit->log('shipment.synced', $request->user(), $shipment, [
            'order_number' => $order->number,
            'previous_status' => $previousStatus,
            'new_status' => $shipment->refresh()->status,
        ]);

        return response()->json([
            'status' => $shipment->status,
        ]);
    }
}
