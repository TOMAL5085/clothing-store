<?php

namespace App\Services\Messaging;

use App\Jobs\SendOutboundMessage;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Services\NotificationPreferenceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MessageDispatcher
{
    /**
     * Notification categories eligible for outbound messaging. Centralized
     * here so the mapping changes in one place without touching any
     * notification class. Admin categories are intentionally absent:
     * store alerts stay on mail/in-app.
     *
     * @var array<string, list<string>>
     */
    public const CHANNEL_CATEGORIES = [
        'order_placed' => ['sms', 'whatsapp'],
        'order_paid' => ['sms', 'whatsapp'],
        'order_payment_failed' => ['sms', 'whatsapp'],
        'order_status_changed' => ['sms', 'whatsapp'],
        'shipment_status_changed' => ['sms', 'whatsapp'],
        'cancellation_completed' => ['sms', 'whatsapp'],
        'refund_completed' => ['sms', 'whatsapp'],
    ];

    public function __construct(
        private readonly MessageGatewayResolver $gateways,
        private readonly NotificationPreferenceService $preferences,
    ) {}

    /**
     * Evaluate one notification send for SMS/WhatsApp delivery. Never
     * throws: messaging must not break checkout, payment, or any other
     * business flow. All call sites already run after transaction commit.
     */
    public function dispatchFor(User $user, object $notification): void
    {
        try {
            $this->dispatch($user, $notification);
        } catch (\Throwable $exception) {
            Log::warning('messaging.dispatch_failed', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function dispatch(User $user, object $notification): void
    {
        if (! method_exists($notification, 'messagingCategory')) {
            return;
        }

        $category = $notification->messagingCategory();
        $channels = self::CHANNEL_CATEGORIES[$category] ?? [];

        if ($channels === []) {
            return;
        }

        $phone = self::normalizePhone($user->phone);

        if ($phone === null) {
            return;
        }

        $messageKey = $notification->messagingUuid ?? (string) Str::uuid();
        $orderNumber = isset($notification->order) && is_object($notification->order)
            ? ($notification->order->number ?? null)
            : null;

        foreach ($channels as $channel) {
            if (! $this->preferences->isChannelEnabled($user, $category, $channel)) {
                continue;
            }

            if (! $this->gateways->configured($channel)) {
                Log::warning('messaging.driver_not_configured', [
                    'channel' => $channel,
                    'user_id' => $user->id,
                ]);

                continue;
            }

            $delivery = NotificationDelivery::query()->firstOrCreate(
                ['message_key' => $messageKey, 'channel' => $channel],
                [
                    'user_id' => $user->id,
                    'channel' => $channel,
                    'recipient' => $phone,
                    'template' => $category,
                    'order_number' => is_string($orderNumber) ? $orderNumber : null,
                    'status' => NotificationDelivery::STATUS_QUEUED,
                    'provider' => $this->gateways->for($channel)->name(),
                ]
            );

            if ($delivery->wasRecentlyCreated) {
                SendOutboundMessage::dispatch(
                    $delivery->id,
                    MessageTemplates::render($notification, $user, $channel)
                );
            }
        }
    }

    /**
     * Normalize to E.164-ish digits: optional leading +, 7–15 digits
     * total. Anything else is not safely dialable and skips delivery.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $clean = preg_replace('/[\s\-().]+/', '', trim($phone));

        if (! is_string($clean) || ! preg_match('/^\+?[1-9]\d{6,14}$/', $clean)) {
            return null;
        }

        return $clean;
    }
}
