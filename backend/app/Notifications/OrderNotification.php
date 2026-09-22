<?php

namespace App\Notifications;

use App\Models\Order;
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
        return ['database', 'mail'];
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
}
