<?php

namespace App\Services;

use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\Couriers\CourierService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderResolutionService
{
    public function __construct(
        private readonly OrderFulfillmentService $fulfillment,
        private readonly CourierService $courierService,
        private readonly NotificationService $notifications,
    ) {}

    public function createCancellationRequest(Order $order, User $user, string $reason): CancellationRequest
    {
        $order->loadMissing('shipment');
        $this->assertCancellable($order);

        if ($order->cancellationRequest()->whereIn('status', ['pending', 'approved', 'executed'])->exists()) {
            throw ValidationException::withMessages(['order' => 'A cancellation request already exists for this order.']);
        }

        $cancellation = $order->cancellationRequest()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'reason' => $reason,
            'requested_at' => now(),
        ])->load(['order', 'refund']);

        DB::afterCommit(function () use ($order, $cancellation) {
            $this->notifications->cancellationRequested($order, $cancellation);
        });

        return $cancellation;
    }

    public function reviewCancellation(CancellationRequest $request, User $admin, string $decision, ?string $adminReason = null): CancellationRequest
    {
        return DB::transaction(function () use ($request, $admin, $decision, $adminReason) {
            $request = CancellationRequest::query()->lockForUpdate()->with(['order.payment', 'refund'])->findOrFail($request->id);

            if ($request->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'This cancellation request has already been reviewed.']);
            }

            if ($decision === 'rejected') {
                $request->update([
                    'status' => 'rejected',
                    'admin_reason' => $adminReason,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                ]);

                DB::afterCommit(function () use ($request) {
                    $this->notifications->cancellationRejected($request->order, $request->fresh());
                });

                return $request->fresh(['order', 'refund']);
            }

            $order = $request->order()->lockForUpdate()->with('payment')->firstOrFail();
            $this->assertCancellable($order);

            $request->update([
                'status' => 'approved',
                'admin_reason' => $adminReason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            DB::afterCommit(function () use ($request) {
                $this->notifications->cancellationApproved($request->order, $request->fresh());
            });

            $this->executeCancellation($request->fresh(['order.payment']), $admin);

            return $request->fresh(['order', 'refund']);
        });
    }

    public function createReturnRequest(Order $order, User $user, array $items, string $reason): ReturnRequest
    {
        $order->loadMissing('items', 'returnRequests.items');
        $this->assertReturnable($order);

        $itemMap = $order->items->keyBy('id');
        $normalized = [];

        foreach ($items as $item) {
            $orderItemId = (int) ($item['order_item_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $orderItem = $itemMap->get($orderItemId);

            if (! $orderItem || $quantity < 1) {
                throw ValidationException::withMessages(['items' => 'Return items must belong to this order and have a valid quantity.']);
            }

            $alreadyRequested = $this->activeReturnedQuantity($order, $orderItemId);
            if ($alreadyRequested + $quantity > $orderItem->quantity) {
                throw ValidationException::withMessages(['items' => "Return quantity for {$orderItem->product_name} exceeds the purchased quantity."]);
            }

            $normalized[$orderItemId] = ($normalized[$orderItemId] ?? 0) + $quantity;
            if ($normalized[$orderItemId] + $alreadyRequested > $orderItem->quantity) {
                throw ValidationException::withMessages(['items' => "Return quantity for {$orderItem->product_name} exceeds the purchased quantity."]);
            }
        }

        if ($normalized === []) {
            throw ValidationException::withMessages(['items' => 'At least one return item is required.']);
        }

        return DB::transaction(function () use ($order, $user, $reason, $normalized) {
            $return = $order->returnRequests()->create([
                'user_id' => $user->id,
                'status' => 'pending',
                'reason' => $reason,
                'requested_at' => now(),
            ]);

            foreach ($normalized as $orderItemId => $quantity) {
                $return->items()->create([
                    'order_item_id' => $orderItemId,
                    'quantity' => $quantity,
                    'resolution_status' => 'requested',
                ]);
            }

            DB::afterCommit(function () use ($order, $return) {
                $this->notifications->returnRequested($order, $return->fresh(['items']));
            });

            return $return->fresh(['order', 'items.orderItem', 'refund']);
        });
    }

    public function reviewReturn(ReturnRequest $return, User $admin, string $decision, ?string $adminReason = null): ReturnRequest
    {
        return DB::transaction(function () use ($return, $admin, $decision, $adminReason) {
            $return = ReturnRequest::query()->lockForUpdate()->with(['items.orderItem', 'refund', 'order.payment'])->findOrFail($return->id);

            if (! in_array($return->status, ['pending', 'approved'], true)) {
                throw ValidationException::withMessages(['status' => 'This return request cannot be reviewed in its current state.']);
            }

            if ($decision === 'rejected') {
                $return->update([
                    'status' => 'rejected',
                    'admin_reason' => $adminReason,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                ]);
                $return->items()->update(['resolution_status' => 'rejected']);

                DB::afterCommit(function () use ($return) {
                    $this->notifications->returnRejected($return->order, $return->fresh());
                });

                return $return->fresh(['order', 'items.orderItem', 'refund']);
            }

            $return->update([
                'status' => 'approved',
                'admin_reason' => $adminReason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);
            $return->items()->update(['resolution_status' => 'approved']);

            DB::afterCommit(function () use ($return) {
                $this->notifications->returnApproved($return->order, $return->fresh());
            });

            return $return->fresh(['order', 'items.orderItem', 'refund']);
        });
    }

    public function markReturnReceived(ReturnRequest $return, User $admin, ?string $adminReason = null): ReturnRequest
    {
        return DB::transaction(function () use ($return, $admin, $adminReason) {
            $return = ReturnRequest::query()->lockForUpdate()->with(['items.orderItem', 'refund', 'order.payment'])->findOrFail($return->id);

            if ($return->status !== 'approved') {
                throw ValidationException::withMessages(['status' => 'Only approved returns can be marked received.']);
            }

            $return->update([
                'status' => 'received',
                'admin_reason' => $adminReason ?? $return->admin_reason,
                'reviewed_by' => $return->reviewed_by ?? $admin->id,
                'received_at' => now(),
            ]);
            $return->items()->update(['resolution_status' => 'received']);

            $refund = $this->createRefundForReturn($return->fresh(['items.orderItem', 'order.payment']), $admin);

            DB::afterCommit(function () use ($return, $refund) {
                $this->notifications->returnReceived($return->order, $return->fresh());
                $this->notifications->refundCreated($return->order, $refund->fresh());
            });

            return $return->fresh(['order', 'items.orderItem', 'refund']);
        });
    }

    public function updateRefund(Refund $refund, User $admin, string $status, ?string $providerReference = null, ?string $failureReason = null): Refund
    {
        $oldStatus = $refund->status;

        $updated = DB::transaction(function () use ($refund, $admin, $status, $providerReference, $failureReason) {
            $refund = Refund::query()->lockForUpdate()->findOrFail($refund->id);

            if (in_array($refund->status, ['succeeded', 'canceled'], true)) {
                throw ValidationException::withMessages(['status' => 'This refund is already finalized.']);
            }

            $refund->update([
                'status' => $status,
                'provider_reference' => $providerReference ?? $refund->provider_reference,
                'failure_reason' => $failureReason,
                'processed_by' => $admin->id,
                'processed_at' => in_array($status, ['succeeded', 'failed', 'canceled'], true) ? now() : $refund->processed_at,
            ]);

            if ($status === 'succeeded' && $refund->return_request_id) {
                $refund->returnRequest?->update(['status' => 'resolved', 'resolved_at' => now()]);
                $refund->returnRequest?->items()->update(['resolution_status' => 'refunded']);
            }

            return $refund->fresh(['order', 'returnRequest', 'cancellationRequest']);
        });

        DB::afterCommit(function () use ($updated, $oldStatus) {
            if ($oldStatus !== $updated->status) {
                match ($updated->status) {
                    'processing' => $this->notifications->refundProcessing($updated->order, $updated),
                    'succeeded' => $this->notifications->refundCompleted($updated->order, $updated),
                    'failed' => $this->notifications->refundFailed($updated->order, $updated),
                    default => null,
                };
            }
        });

        return $updated;
    }

    private function executeCancellation(CancellationRequest $request, User $admin): void
    {
        $order = $request->order()->with(['payment', 'items', 'shipment'])->lockForUpdate()->firstOrFail();

        if ($request->executed_at) {
            return;
        }

        if ($order->inventory_decremented_at) {
            $this->fulfillment->releaseInventory($order);
            $request->update(['inventory_restocked_at' => now()]);
        }

        // Cancel courier shipment if exists and not already delivered/failed
        if ($order->shipment && in_array($order->shipment->status, ['pending', 'processing', 'ready_to_ship', 'shipped', 'in_transit', 'out_for_delivery'], true)) {
            $this->courierService->gateway()->cancelShipment($order->shipment);
            $order->shipment->update(['status' => 'failed_delivery']);
        }

        if ($order->payment_status === 'paid') {
            $refund = $this->createRefundForCancellation($request->fresh(['order.payment']), $admin);
        } else {
            $order->payment?->update(['status' => 'canceled', 'failure_reason' => 'Order cancellation approved.']);
            $order->update(['payment_status' => 'canceled']);
        }

        $order->update(['status' => 'cancelled']);
        $request->update(['status' => 'executed', 'executed_at' => now()]);

        DB::afterCommit(function () use ($request) {
            $this->notifications->cancellationCompleted($request->order, $request->fresh());
        });
    }

    private function createRefundForCancellation(CancellationRequest $request, User $admin): Refund
    {
        $order = $request->order;
        if ($existing = $request->refund) {
            return $existing;
        }

        return Refund::create([
            'order_id' => $order->id,
            'payment_id' => $order->payment?->id,
            'cancellation_request_id' => $request->id,
            'requested_by' => $request->user_id,
            'processed_by' => $admin->id,
            'provider' => $order->payment_provider,
            'status' => 'pending',
            'amount' => $this->remainingRefundableAmount($order),
            'currency' => $order->currency,
            'reason' => 'order_cancellation',
            'requested_at' => now(),
        ]);
    }

    private function createRefundForReturn(ReturnRequest $return, User $admin): Refund
    {
        if ($existing = $return->refund) {
            return $existing;
        }

        $amount = $return->items->sum(fn ($item) => (float) $item->orderItem->unit_price * $item->quantity);
        $amount = min($amount, $this->remainingRefundableAmount($return->order));

        $return->items()->update(['resolution_status' => 'refund_pending']);

        return Refund::create([
            'order_id' => $return->order_id,
            'payment_id' => $return->order->payment?->id,
            'return_request_id' => $return->id,
            'requested_by' => $return->user_id,
            'processed_by' => $admin->id,
            'provider' => $return->order->payment_provider,
            'status' => 'pending',
            'amount' => $amount,
            'currency' => $return->order->currency,
            'reason' => 'return',
            'requested_at' => now(),
        ]);
    }

    private function remainingRefundableAmount(Order $order): float
    {
        $refunded = $order->refunds()->whereIn('status', ['pending', 'processing', 'succeeded'])->sum('amount');

        return max(0, round((float) $order->total - (float) $refunded, 2));
    }

    private function assertCancellable(Order $order): void
    {
        if (! in_array($order->status, config('orders.cancellation.allowed_order_statuses', []), true)) {
            throw ValidationException::withMessages(['order' => 'This order cannot be cancelled in its current status.']);
        }

        $shipmentStatus = $order->shipment?->status;
        if ($shipmentStatus && in_array($shipmentStatus, config('orders.cancellation.blocked_shipment_statuses', []), true)) {
            throw ValidationException::withMessages(['order' => 'This order is already in fulfillment and cannot be cancelled.']);
        }
    }

    private function assertReturnable(Order $order): void
    {
        if (! in_array($order->status, config('orders.returns.allowed_order_statuses', []), true)) {
            throw ValidationException::withMessages(['order' => 'Only delivered orders are eligible for return.']);
        }

        if ($order->payment_status !== 'paid') {
            throw ValidationException::withMessages(['order' => 'Only paid orders are eligible for return.']);
        }
    }

    private function activeReturnedQuantity(Order $order, int $orderItemId): int
    {
        return (int) $order->returnRequests
            ->whereNotIn('status', ['rejected'])
            ->flatMap->items
            ->where('order_item_id', $orderItemId)
            ->sum('quantity');
    }
}
