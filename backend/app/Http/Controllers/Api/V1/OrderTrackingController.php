<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderTrackingController extends Controller
{
public function show(Request $request, Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        $order->load(['items', 'shippingAddress', 'payment', 'shipment']);
        
        if ($order->shipment) {
            $order->shipment->load('events');
        }

        return OrderResource::make($order);
    }
}