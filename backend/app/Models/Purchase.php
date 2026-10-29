<?php

namespace App\Models;

use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    public const STATUSES = ['draft', 'ordered', 'received', 'cancelled'];

    public const PAYMENT_STATUSES = ['pending', 'partial', 'paid', 'overpaid'];

    protected $fillable = [
        'supplier_id',
        'reference_number',
        'status',
        'subtotal',
        'tax',
        'total',
        'payment_status',
        'expected_date',
        'received_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'expected_date' => 'date',
            'received_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class)->orderBy('id');
    }

    public function ledger(): HasMany
    {
        return $this->hasMany(SupplierLedger::class)->where('reference_type', 'purchase')->orderBy('created_at', 'desc');
    }
}
