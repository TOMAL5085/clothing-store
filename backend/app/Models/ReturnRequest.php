<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReturnRequest extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected', 'received', 'resolved'];

    protected $fillable = [
        'order_id',
        'user_id',
        'status',
        'reason',
        'admin_reason',
        'reviewed_by',
        'requested_at',
        'reviewed_at',
        'received_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'received_at' => 'datetime',
            'resolved_at' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class);
    }
}
