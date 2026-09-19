<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOtp extends Model
{
    protected $fillable = [
        'user_id',
        'purpose',
        'channel',
        'code_hash',
        'attempts',
        'last_sent_at',
        'expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
