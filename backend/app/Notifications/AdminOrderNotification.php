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
use Illuminate\Support\Str;

abstract class AdminOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $messagingUuid;

    public function __construct(
        public Order $order,
        protected string $title,
        protected string $message,
        protected string $category,
        protected ?string $actionUrl = null,
    ) {
        $this->messagingUuid = (string) Str::uuid();
    }

    /**
     * @see OrderNotification::messagingCategory()
     */
    public function messagingCategory(): string
    {
        return $this->category;
    }

    public function via(object $notifiable): array
    {
        $channels = ['database', 'mail', 'broadcast'];

        if ($notifiable instanceof User) {
            return app(NotificationPreferenceService::class)
                ->effectiveChannels($notifiable, $this->category, $channels);
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $customerName = $this->order->user?->name ?? 'Guest';
        $customerEmail = $this->order->user?->email ?? 'N/A';

        $mail = (new MailMessage)
            ->subject("[ADMIN] {$this->title}")
            ->greeting('Hello Admin,')
            ->line($this->message)
            ->line("Order Number: {$this->order->number}")
            ->line("Customer: {$customerName} ({$customerEmail})")
            ->line("Order Total: {$this->order->currency} ".number_format((float) $this->order->total, 2));

        if ($this->actionUrl) {
            $mail->action('View Order', $this->actionUrl);
        }

        return $mail->line('— JAAJ Admin System');
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
            'customer_name' => $this->order->user?->name ?? 'Guest',
            'customer_email' => $this->order->user?->email ?? 'N/A',
            'action_url' => $this->actionUrl,
        ];
    }

    abstract protected function getNotificationType(): string;

    /**
     * All admins share one private channel authorized to admin users only
     * (routes/channels.php). Per-recipient preference filtering still
     * happens in via(), so an admin who disabled in-app delivery simply
     * never emits here while others do.
     *
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.notifications')];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Explicit minimal broadcast payload. Customer emails stay out of the
     * socket payload even though admins may see them in the persisted
     * record; the frontend refetches details over HTTPS when needed.
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
            'customer_name' => $this->order->user?->name ?? 'Guest',
            'action_url' => $this->actionUrl,
            'read_at' => null,
        ];
    }
}
