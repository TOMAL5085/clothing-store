<?php

namespace App\Models;

use Database\Factories\NotificationDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    /** @use HasFactory<NotificationDeliveryFactory> */
    use HasFactory;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    /**
     * `delivered` is reserved for future provider delivery-receipt
     * webhooks; no code path sets it in this phase.
     */
    public const STATUS_DELIVERED = 'delivered';

    protected $fillable = [
        'message_key',
        'notification_id',
        'user_id',
        'channel',
        'recipient',
        'template',
        'order_number',
        'status',
        'provider',
        'provider_message_id',
        'attempts',
        'last_attempted_at',
        'delivered_at',
        'failed_at',
        'error_code',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'last_attempted_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_DELIVERED], true);
    }
}
