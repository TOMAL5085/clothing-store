<?php

namespace App\Models;

use Database\Factories\MarketingAttributionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingAttribution extends Model
{
    /** @use HasFactory<MarketingAttributionFactory> */
    use HasFactory;

    protected $fillable = [
        'anonymous_id',
        'user_id',
        'session_id',
        'source',
        'medium',
        'campaign',
        'term',
        'content',
        'first_source',
        'first_medium',
        'first_campaign',
        'click_ids',
        'landing_url',
        'referrer',
        'first_seen_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'click_ids' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MarketingEvent::class, 'attribution_id');
    }
}
