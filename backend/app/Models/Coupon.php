<?php

namespace App\Models;

use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'is_active',
        'starts_at',
        'expires_at',
        'minimum_order_amount',
        'maximum_discount_amount',
        'usage_limit',
        'used_count',
        'per_customer_limit',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'minimum_order_amount' => 'decimal:2',
            'maximum_discount_amount' => 'decimal:2',
        ];
    }

    public function isRedeemable(): bool
    {
        return $this->is_active
            && ($this->starts_at === null || $this->starts_at->isPast())
            && ($this->expires_at === null || $this->expires_at->isFuture())
            && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    public function validateFor(float $subtotal, ?User $user = null): void
    {
        if (! $this->isRedeemable()) {
            throw ValidationException::withMessages(['promo_code' => 'That promo code is not valid.']);
        }

        if ($subtotal < (float) $this->minimum_order_amount) {
            throw ValidationException::withMessages(['promo_code' => 'Your bag does not meet the minimum spend for that code.']);
        }

        if ($user && $this->per_customer_limit !== null) {
            $uses = Order::query()
                ->where('user_id', $user->id)
                ->where('promo_code', $this->code)
                ->where('payment_status', 'paid')
                ->count();

            if ($uses >= $this->per_customer_limit) {
                throw ValidationException::withMessages(['promo_code' => 'That promo code has already been used on this account.']);
            }
        }
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'fixed'
            ? (float) $this->value
            : $subtotal * ((float) $this->value / 100);

        if ($this->maximum_discount_amount !== null) {
            $discount = min($discount, (float) $this->maximum_discount_amount);
        }

        return round(min($discount, $subtotal), 2);
    }
}
