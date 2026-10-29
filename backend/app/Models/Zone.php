<?php

namespace App\Models;

use Database\Factories\ZoneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    /** @use HasFactory<ZoneFactory> */
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'shipping_cost', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'shipping_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class)->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
