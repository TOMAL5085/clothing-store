<?php

namespace App\Services\Messaging;

use App\Models\User;
use Illuminate\Notifications\Notification;

class MessageTemplates
{
    public const SMS_LIMIT = 160;

    public const WHATSAPP_LIMIT = 1000;

    /**
     * Build the outbound body from the notification's own curated title and
     * message, so SMS/WhatsApp copy can never drift from in-app/email
     * content. Only whitelisted fields are used; the notification's own
     * action URL (which may carry an owner token) is replaced with the
     * token-free account page.
     */
    public static function render(object $notification, User $user, string $channel): string
    {
        $data = $notification instanceof Notification && method_exists($notification, 'toArray')
            ? $notification->toArray($user)
            : [];

        $title = is_string($data['title'] ?? null) ? $data['title'] : 'Order update';
        $message = is_string($data['message'] ?? null) ? $data['message'] : '';
        $accountUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/').'/account?tab=orders';

        $text = preg_replace('/\s+/', ' ', "JAAJ: {$title} {$message} Details: {$accountUrl}");
        $text = trim((string) $text);

        $limit = $channel === 'whatsapp' ? self::WHATSAPP_LIMIT : self::SMS_LIMIT;

        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit - 1).'…' : $text;
    }
}
