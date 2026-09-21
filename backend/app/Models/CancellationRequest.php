<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CancellationRequest extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected', 'executed'];

    protected $fillable = [
        'order_id',
        'user_id',
        'status',
        'reason',
        'admin_reason',
        'reviewed_by',
        'requested_at',
        'reviewed_at',
        'executed_at',
        'inventory_restocked_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'executed_at' => 'datetime',
            'inventory_restocked_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class);
    }
}
