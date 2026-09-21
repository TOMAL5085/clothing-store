<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    public const RESOLUTION_STATUSES = ['requested', 'approved', 'rejected', 'received', 'refund_pending', 'refunded'];

    protected $fillable = [
        'return_request_id',
        'order_item_id',
        'quantity',
        'resolution_status',
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
