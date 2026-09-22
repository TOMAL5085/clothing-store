<?php

namespace App\Models;

use Database\Factories\MarketingEventDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingEventDelivery extends Model
{
    /** @use HasFactory<MarketingEventDeliveryFactory> */
    use HasFactory;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'marketing_event_id',
        'provider',
        'status',
        'attempts',
        'provider_event_id',
        'last_attempted_at',
        'sent_at',
        'failed_at',
        'error_code',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'last_attempted_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(MarketingEvent::class, 'marketing_event_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_SENT], true);
    }
}
