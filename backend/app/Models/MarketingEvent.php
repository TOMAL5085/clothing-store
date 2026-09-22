<?php

namespace App\Models;

use Database\Factories\MarketingEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingEvent extends Model
{
    /** @use HasFactory<MarketingEventFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_name',
        'event_source',
        'user_id',
        'anonymous_id',
        'session_id',
        'order_id',
        'order_number',
        'product_external_id',
        'occurred_at',
        'received_at',
        'currency',
        'value',
        'attribution_id',
        'metadata',
        'consent_state',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
            'value' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function attribution(): BelongsTo
    {
        return $this->belongsTo(MarketingAttribution::class, 'attribution_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(MarketingEventDelivery::class);
    }

    /**
     * Identity rule for duplicate-submission reads: a row belongs either
     * to its owning authenticated user or to whoever presents the row's
     * own anonymous identifier. Anything else must look like a 404.
     */
    public function belongsToIdentity(?User $user, ?string $anonymousId): bool
    {
        if ($this->user_id !== null) {
            return $user !== null && (int) $user->id === (int) $this->user_id;
        }

        return is_string($anonymousId)
            && $anonymousId !== ''
            && $anonymousId === $this->anonymous_id;
    }
}
