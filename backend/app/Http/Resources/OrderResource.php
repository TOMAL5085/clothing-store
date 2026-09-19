<?php

namespace App\Http\Resources;

use App\Http\Resources\ShipmentEventResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->number,
            'status' => str($this->status)->headline()->toString(),
            'statusCode' => $this->status,
            'paymentStatus' => $this->payment_status,
            'paymentProvider' => $this->payment_provider,
            'countryCode' => $this->country_code,
            'currency' => $this->currency,
            'checkoutToken' => $this->when(
                $request->user()?->isAdmin() || $request->boolean('include_token') || $request->query('checkout_token'),
                $this->checkout_token,
            ),
            'method' => $this->delivery_method,
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'shipping' => (float) $this->shipping,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'promoCode' => $this->promo_code,
            'createdAt' => $this->created_at?->toISOString(),
            'customer' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null),
            'address' => [
                'firstName' => $this->shippingAddress?->first_name,
                'lastName' => $this->shippingAddress?->last_name,
                'email' => $this->shippingAddress?->email,
                'address' => $this->shippingAddress?->address,
                'city' => $this->shippingAddress?->city,
                'postalCode' => $this->shippingAddress?->postal_code,
                'country' => $this->shippingAddress?->country,
            ],
            'billingAddress' => $this->when($this->billingAddress !== null, [
                'firstName' => $this->billingAddress?->first_name,
                'lastName' => $this->billingAddress?->last_name,
                'email' => $this->billingAddress?->email,
                'address' => $this->billingAddress?->address,
                'city' => $this->billingAddress?->city,
                'postalCode' => $this->billingAddress?->postal_code,
                'country' => $this->billingAddress?->country,
            ]),
            'payment' => $this->whenLoaded('payment', fn () => $this->payment ? [
                'provider' => $this->payment->provider,
                'status' => $this->payment->status,
                'reference' => $this->payment->reference,
                'amount' => (float) $this->payment->amount,
                'currency' => $this->payment->currency,
                'method' => $this->payment->method,
            ] : null),
            'shipment' => $this->whenLoaded('shipment', fn () => $this->shipment ? [
                'id' => $this->shipment->id,
                'status' => str($this->shipment->status)->headline()->toString(),
                'statusCode' => $this->shipment->status,
                'carrier' => $this->shipment->carrier,
                'trackingNumber' => $this->shipment->tracking_number,
                'trackingReference' => $this->shipment->tracking_reference,
                'shippingFee' => $this->shipment->shipping_fee ? (float) $this->shipment->shipping_fee : null,
                'estimatedDeliveryAt' => $this->shipment->estimated_delivery_at?->toISOString(),
                'shippedAt' => $this->shipment->shipped_at?->toISOString(),
                'deliveredAt' => $this->shipment->delivered_at?->toISOString(),
                'events' => $this->shipment->relationLoaded('events') 
                    ? ShipmentEventResource::collection($this->shipment->events) 
                    : null,
            ] : null),
            'lines' => $this->items->map(fn ($item) => [
                'id' => "{$item->product_external_id}__{$item->size}",
                'productId' => $item->product_external_id,
                'productName' => $item->product_name,
                'productSlug' => $item->product_slug,
                'size' => $item->size,
                'qty' => $item->quantity,
                'unitPrice' => (float) $item->unit_price,
                'lineTotal' => (float) $item->line_total,
            ])->values(),
        ];
    }
}
