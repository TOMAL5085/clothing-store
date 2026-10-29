<?php

namespace App\Models;

use Database\Factories\ManualPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualPayment extends Model
{
    /** @use HasFactory<ManualPaymentFactory> */
    use HasFactory;

    public const STATUSES = ['pending', 'verified', 'rejected'];

    public const PAYMENT_METHODS = ['cash', 'bank_transfer', 'mobile_money', 'cheque', 'other'];

    protected $fillable = [
        'reference_number',
        'order_id',
        'account_id',
        'customer_name',
        'customer_email',
        'payment_method',
        'amount',
        'currency',
        'payment_date',
        'status',
        'transaction_id',
        'notes',
        'receipt_path',
        'verified_by',
        'verified_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference_number';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
