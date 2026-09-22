<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use App\Services\NotificationPreferenceService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class OrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        protected string $title,
        protected string $message,
        protected string $category,
        protected ?string $actionUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'mail', 'broadcast'];

        // Preference filtering is centralized here so no concrete
        // notification class needs its own channel logic. Guests have no
        // preferences row, so non-user notifiables keep full delivery.
        if ($notifiable instanceof User) {
            return app(NotificationPreferenceService::class)
                ->effectiveChannels($notifiable, $this->category, $channels);
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting("Hello {$notifiable->name},")
            ->line($this->message)
            ->line("Order Number: {$this->order->number}")
            ->line("Order Total: {$this->order->currency} ".number_format((float) $this->order->total, 2));

        if ($this->actionUrl) {
            $mail->action('View Order', $this->actionUrl);
        }

        return $mail->line('Thank you for shopping with JAAJ!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->getNotificationType(),
            'title' => $this->title,
            'message' => $this->message,
            'category' => $this->category,
            'order_id' => $this->order->id,
            'order_number' => $this->order->number,
            'action_url' => $this->actionUrl,
        ];
    }

    abstract protected function getNotificationType(): string;

    /**
     * Each customer receives their own notifications on their private user
     * channel. Channel access is authorized server-side in
     * routes/channels.php; the frontend can never subscribe as another user.
     *
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.'.$this->order->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Explicit minimal broadcast payload. The database record stays the
     * source of truth — the frontend refetches it — so only renderable
     * fields travel over the socket. No emails, tokens, or payment data.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->getNotificationType(),
            'title' => $this->title,
            'message' => $this->message,
            'category' => $this->category,
            'order_number' => $this->order->number,
            'action_url' => $this->actionUrl,
            'read_at' => null,
        ];
    }
}
