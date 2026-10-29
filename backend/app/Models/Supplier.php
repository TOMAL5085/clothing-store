<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    public const STATUSES = ['active', 'inactive', 'suspended'];

    protected $fillable = [
        'name',
        'slug',
        'contact_person',
        'email',
        'phone',
        'tax_id',
        'address',
        'city_id',
        'zone_id',
        'status',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class)->orderBy('created_at', 'desc');
    }

    public function ledger(): HasMany
    {
        return $this->hasMany(SupplierLedger::class)->orderBy('created_at', 'desc');
    }
}
