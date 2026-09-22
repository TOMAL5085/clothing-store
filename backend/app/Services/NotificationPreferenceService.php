<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class NotificationPreferenceService
{
    /**
     * Every category produced by the notification system. Keys are the exact
     * `category` values stored in the notifications table; labels are for
     * display only. All 17 customer + 6 admin categories are configurable —
     * notifications are informational, so no workflow depends on delivery
     * and no category is force-enabled.
     *
     * @return array<string, string> category => label
     */
    public static function catalog(): array
    {
        return [
            'order_placed' => 'Order placed',
            'order_paid' => 'Payment confirmed',
            'order_payment_failed' => 'Payment failed',
            'order_status_changed' => 'Order status updates',
            'shipment_status_changed' => 'Shipment updates',
            'cancellation_requested' => 'Cancellation requested',
            'cancellation_approved' => 'Cancellation approved',
            'cancellation_rejected' => 'Cancellation rejected',
            'cancellation_completed' => 'Cancellation completed',
            'return_requested' => 'Return requested',
            'return_approved' => 'Return approved',
            'return_rejected' => 'Return rejected',
            'return_received' => 'Return received',
            'refund_created' => 'Refund created',
            'refund_processing' => 'Refund processing',
            'refund_completed' => 'Refund completed',
            'refund_failed' => 'Refund failed',
            'admin_new_order' => 'New order (admin)',
            'admin_order_paid' => 'Order paid (admin)',
            'admin_cancellation_requested' => 'Cancellation request (admin)',
            'admin_return_requested' => 'Return request (admin)',
            'admin_refund_action_required' => 'Refund action required (admin)',
            'admin_shipment_problem' => 'Shipment problem (admin)',
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return [
            'in_app_enabled' => true,
            'email_enabled' => true,
            'sms_enabled' => true,
            'whatsapp_enabled' => true,
        ];
    }

    /**
     * Effective preference row per catalog category for the user. Missing
     * rows fall back to defaults, so behavior is unchanged until the user
     * explicitly opts out.
     *
     * @return list<array{category:string, label:string, in_app_enabled:bool, email_enabled:bool, sms_enabled:bool, whatsapp_enabled:bool}>
     */
    public function forUser(User $user): array
    {
        $stored = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('category');

        $result = [];
        foreach (self::catalog() as $category => $label) {
            $row = $stored->get($category);
            $result[] = [
                'category' => $category,
                'label' => $label,
                'in_app_enabled' => $row ? (bool) $row->in_app_enabled : true,
                'email_enabled' => $row ? (bool) $row->email_enabled : true,
                'sms_enabled' => $row ? (bool) $row->sms_enabled : true,
                'whatsapp_enabled' => $row ? (bool) $row->whatsapp_enabled : true,
            ];
        }

        return $result;
    }

    /**
     * Persist the given per-category channel flags for the user's own
     * preferences. Unknown categories are rejected, not stored.
     *
     * @param  list<array{category:string, in_app_enabled?:bool, email_enabled?:bool, sms_enabled?:bool, whatsapp_enabled?:bool}>  $preferences
     * @return list<array{category:string, label:string, in_app_enabled:bool, email_enabled:bool, sms_enabled:bool, whatsapp_enabled:bool}>
     */
    public function updateForUser(User $user, array $preferences): array
    {
        $catalog = self::catalog();

        foreach ($preferences as $preference) {
            $category = $preference['category'] ?? null;

            if (! is_string($category) || ! array_key_exists($category, $catalog)) {
                throw ValidationException::withMessages([
                    'preferences' => "Unknown notification category [{$category}].",
                ]);
            }

            NotificationPreference::query()->updateOrCreate(
                ['user_id' => $user->id, 'category' => $category],
                [
                    'in_app_enabled' => (bool) ($preference['in_app_enabled'] ?? true),
                    'email_enabled' => (bool) ($preference['email_enabled'] ?? true),
                    'sms_enabled' => (bool) ($preference['sms_enabled'] ?? true),
                    'whatsapp_enabled' => (bool) ($preference['whatsapp_enabled'] ?? true),
                ]
            );
        }

        return $this->forUser($user);
    }

    /**
     * Single-channel check used by outbound messaging. Missing rows mean
     * enabled, matching the API defaults.
     */
    public function isChannelEnabled(User $user, string $category, string $channel): bool
    {
        $flag = match ($channel) {
            'sms' => 'sms_enabled',
            'whatsapp' => 'whatsapp_enabled',
            'mail' => 'email_enabled',
            default => 'in_app_enabled',
        };

        $row = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('category', $category)
            ->first();

        return $row ? (bool) $row->{$flag} : true;
    }

    /**
     * Filter a notification's delivery channels through the recipient's
     * preferences. The database + broadcast transports share the in-app
     * flag (broadcast only feeds the in-app bell); mail uses the email
     * flag. Unknown categories pass through unchanged so future
     * notification types keep working until catalogued.
     *
     * @param  list<string>  $channels
     * @return list<string>
     */
    public function effectiveChannels(User $user, string $category, array $channels): array
    {
        if (! array_key_exists($category, self::catalog())) {
            return array_values($channels);
        }

        $row = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('category', $category)
            ->first();

        if (! $row) {
            return array_values($channels);
        }

        return array_values(array_filter($channels, function (string $channel) use ($row) {
            return match ($channel) {
                'database', 'broadcast' => (bool) $row->in_app_enabled,
                'mail' => (bool) $row->email_enabled,
                default => false,
            };
        }));
    }
}
