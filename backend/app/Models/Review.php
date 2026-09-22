<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    public const STATUSES = ['pending', 'approved', 'rejected'];

    /**
     * Moderation state and purchase verification are server-managed and must
     * never be mass-assigned from request input.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'title',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'verified_purchase' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
