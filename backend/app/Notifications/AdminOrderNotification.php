<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class AdminOrderNotification extends Notification implements ShouldQueue
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
        return ['database', 'mail'];
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
}
