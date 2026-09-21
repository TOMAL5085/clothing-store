<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    public const STATUSES = ['pending', 'processing', 'succeeded', 'failed', 'canceled'];

    protected $fillable = [
        'order_id',
        'payment_id',
        'return_request_id',
        'cancellation_request_id',
        'requested_by',
        'processed_by',
        'provider',
        'status',
        'amount',
        'currency',
        'reason',
        'provider_reference',
        'failure_reason',
        'payload',
        'requested_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payload' => 'array',
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function cancellationRequest(): BelongsTo
    {
        return $this->belongsTo(CancellationRequest::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
