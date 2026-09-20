<?php

namespace App\Models;

use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;
    public const STATUSES = [
        'pending',
        'processing',
        'ready_to_ship',
        'shipped',
        'in_transit',
        'out_for_delivery',
        'delivered',
        'failed_delivery',
    ];

    public const TRANSITIONS = [
        'pending' => ['processing', 'ready_to_ship', 'cancelled'],
        'processing' => ['ready_to_ship', 'cancelled'],
        'ready_to_ship' => ['shipped', 'cancelled'],
        'shipped' => ['in_transit', 'out_for_delivery', 'delivered', 'failed_delivery'],
        'in_transit' => ['out_for_delivery', 'delivered', 'failed_delivery'],
        'out_for_delivery' => ['delivered', 'failed_delivery'],
        'delivered' => [],
        'failed_delivery' => ['out_for_delivery', 'delivered'],
    ];

    protected $fillable = [
        'order_id',
        'status',
        'carrier',
        'tracking_number',
        'tracking_reference',
        'shipping_fee',
        'estimated_delivery_at',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_fee' => 'decimal:2',
            'estimated_delivery_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)->orderBy('occurred_at');
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? [], true);
    }
}