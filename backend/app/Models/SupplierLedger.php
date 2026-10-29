<?php

namespace App\Models;

use Database\Factories\SupplierLedgerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierLedger extends Model
{
    /** @use HasFactory<SupplierLedgerFactory> */
    use HasFactory;

    protected $table = 'supplier_ledger';

    protected $fillable = [
        'supplier_id',
        'reference_type',
        'reference_id',
        'debit',
        'credit',
        'balance',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
