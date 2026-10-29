<?php

namespace App\Models;

use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    public const TYPES = ['asset', 'liability', 'equity', 'revenue', 'expense'];

    protected $fillable = [
        'code',
        'name',
        'type',
        'subtype',
        'opening_balance',
        'current_balance',
        'description',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class)->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class)->orderBy('expense_date', 'desc');
    }
}
