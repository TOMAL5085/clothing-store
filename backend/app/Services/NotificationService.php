<?php

namespace App\Services;

use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\Shipment;
use App\Models\User;
use App\Notifications\AdminCancellationRequestNotification;
use App\Notifications\AdminNewOrderNotification;
use App\Notifications\AdminOrderPaidNotification;
use App\Notifications\AdminRefundActionRequiredNotification;
use App\Notifications\AdminReturnRequestNotification;
use App\Notifications\AdminShipmentProblemNotification;
use App\Notifications\CancellationApprovedNotification;
use App\Notifications\CancellationCompletedNotification;
use App\Notifications\CancellationRejectedNotification;
use App\Notifications\CancellationRequestedNotification;
use App\Notifications\OrderPaidNotification;
use App\Notifications\OrderPaymentFailedNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderStatusChangedNotification;
use App\Notifications\RefundCompletedNotification;
use App\Notifications\RefundCreatedNotification;
use App\Notifications\RefundFailedNotification;
use App\Notifications\RefundProcessingNotification;
use App\Notifications\ReturnApprovedNotification;
use App\Notifications\ReturnReceivedNotification;
use App\Notifications\ReturnRejectedNotification;
use App\Notifications\ReturnRequestedNotification;
use App\Notifications\ShipmentStatusChangedNotification;
use App\Services\Messaging\MessageDispatcher;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Safely notify a user if they exist and have notifications enabled
     */
    private function notifyUser(User $user, Notification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            Log::warning('Failed to send notification', [
                'user_id' => $user->id,
                'notification' => get_class($notification),
                'error' => $e->getMessage(),
            ]);
        }

        // Outbound SMS/WhatsApp fan-out. Evaluated after the in-app/email
        // send; every call site already runs after transaction commit, and
        // the dispatcher never throws into business flows.
        app(MessageDispatcher::class)->dispatchFor($user, $notification);
    }

    /**
     * Send notification to customer and admins when order is placed
     */
    public function orderPlaced(Order $order): void
    {
        // Customer notification
        if ($order->user) {
            $this->notifyUser($order->user, new OrderPlacedNotification($order));
        }

        // Admin notifications
        $this->notifyAdmins(new AdminNewOrderNotification($order));
    }

    /**
     * Send notification when order payment is confirmed
     */
    public function orderPaid(Order $order, string $oldStatus = 'pending'): void
    {
        // Customer notification
        if ($order->user) {
            $this->notifyUser($order->user, new OrderPaidNotification($order));
        }

        // Admin notification
        $this->notifyAdmins(new AdminOrderPaidNotification($order));

        // Also send status changed if it wasn't pending before
        if ($oldStatus !== 'pending' && $order->user) {
            $this->notifyUser($order->user, new OrderStatusChangedNotification($order, $oldStatus, $order->status));
        }
    }

    /**
     * Send notification when order payment fails
     */
    public function orderPaymentFailed(Order $order, string $reason = 'Payment could not be processed'): void
    {
        if ($order->user) {
            $this->notifyUser($order->user, new OrderPaymentFailedNotification($order, $reason));
        }
    }

    /**
     * Send notification when order status changes
     */
    public function orderStatusChanged(Order $order, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus === $newStatus || ! $order->user) {
            return;
        }

        $this->notifyUser($order->user, new OrderStatusChangedNotification($order, $oldStatus, $newStatus));
    }

    /**
     * Send notification when shipment status changes
     */
    public function shipmentStatusChanged(Order $order, Shipment $shipment, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus === $newStatus) {
            return;
        }

        if ($order->user) {
            $this->notifyUser($order->user, new ShipmentStatusChangedNotification($order, $shipment, $oldStatus, $newStatus));
        }

        // Notify admins of shipment problems
        if (in_array($newStatus, ['failed_delivery'], true)) {
            $this->notifyAdmins(new AdminShipmentProblemNotification($order, $shipment));
        }
    }

    /**
     * Send notification when cancellation is requested
     */
    public function cancellationRequested(Order $order, CancellationRequest $cancellation): void
    {
        if ($order->user) {
            $this->notifyUser($order->user, new CancellationRequestedNotification($order, $cancellation));
        }
        $this->notifyAdmins(new AdminCancellationRequestNotification($order, $cancellation));
    }

    /**
     * Send notification when cancellation is approved
     */
    public function cancellationApproved(Order $order, CancellationRequest $cancellation): void
    {
        if ($order->user) {
            $this->notifyUser($order->user, new CancellationApprovedNotification($order, $cancellation));
        }
    }

    /**
     * Send notification when cancellation is rejected
     */
    public function cancellationRejected(Order $order, CancellationRequest $cancellation): void
    {
        if ($order->user) {
            $this->notifyUser($order->user, new CancellationRejectedNotification($order, $cancellation));
        }
    }

    /**
     * Send notification when cancellation is completed (executed)
     */
    public function cancellationCompleted(Order $order, CancellationRequest $cancellation): void
    {
        if ($order->user) {
            $this->notifyUser($order->user, new CancellationCompletedNotification($order, $cancellation));
        }
    }

    /**
     * Send notification when return is requested
     */
    public function returnRequested(Order $order, ReturnRequest $return): void
    {
        if ($order->user) {
            $this->notifyUser($order->user, new ReturnRequestedNotification($order, $return));
        }
        $this->notifyAdmins(new AdminReturnRequestNotification($order, $return));
    }

    /**
     * Send notification when return is approved
     */
    public function returnApproved(Order $order, ReturnRequest $return): void
    {
        if ($order->user) {
            $this->notifyUser($order->user, new ReturnApprovedNotification($order, $return));
        }
    }

    /**
     * Send notification when return is rejected
     */
    public function returnRejected(Order $order, ReturnRequest $return): void
    {
        if ($order->user) {
            $this->notifyUser($order->user, new ReturnRejectedNotification($order, $return));
        }
    }

    /**
     * Send notification when return is received
     */
    public function returnReceived(Order $order, ReturnRequest $return): void
    {
        if ($order->user) {
            $this->notifyUser($order->user, new ReturnReceivedNotification($order, $return));
        }
    }

    /**
     * Send notification when refund is created
     */
    public function refundCreated(Order $order, Refund $refund): void
    {
        if ($order->user) {
            $this->notifyUser($order->user, new RefundCreatedNotification($order, $refund));
        }

        // Notify admins if refund needs manual action
        if ($refund->status === 'pending' && $refund->provider !== 'demo') {
            $this->notifyAdmins(new AdminRefundActionRequiredNotification($order, $refund));
        }
    }

    /**
     * Send notification when refund status changes to processing
     */
    public function refundProcessing(Order $order, Refund $refund): void
    {
        if ($order->user) {
            $this->notifyUser($order->user, new RefundProcessingNotification($order, $refund));
        }
    }

    /**
     * Send notification when refund is completed
     */
    public function refundCompleted(Order $order, Refund $refund): void
    {
        if ($order->user) {
            $this->notifyUser($order->user, new RefundCompletedNotification($order, $refund));
        }
    }

    /**
     * Send notification when refund fails
     */
    public function refundFailed(Order $order, Refund $refund): void
    {
        if ($order->user) {
            $this->notifyUser($order->user, new RefundFailedNotification($order, $refund));
        }
    }

    /**
     * Notify all admin users
     */
    private function notifyAdmins(Notification $notification): void
    {
        $admins = User::where('role', 'admin')->where('status', 'active')->get();
        foreach ($admins as $admin) {
            try {
                $admin->notify($notification);
            } catch (\Throwable $e) {
                Log::warning('Failed to send admin notification', [
                    'admin_id' => $admin->id,
                    'notification' => get_class($notification),
                    'error' => $e->getMessage(),
                ]);
            }

            app(MessageDispatcher::class)->dispatchFor($admin, $notification);
        }
    }
}
