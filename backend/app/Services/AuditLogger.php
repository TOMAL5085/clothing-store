<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Metadata keys that must never be persisted, matched case-insensitively
     * as substrings so nested variants (e.g. card_number) are also caught.
     *
     * @var list<string>
     */
    public const SENSITIVE_KEYS = [
        'password',
        'card',
        'cvv',
        'cvc',
        'secret',
        'checkout_token',
        'authorization',
        'bearer',
        'remember_token',
    ];

    public function log(string $action, ?User $actor, ?Model $auditable = null, array $metadata = []): AuditLog
    {
        $request = request();

        return AuditLog::query()->create([
            'actor_id' => $actor?->getAuthIdentifier(),
            'actor_role' => $actor?->role,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500) ?: null,
            'metadata' => $this->sanitize($metadata),
        ]);
    }

    /**
     * Recursively strip sensitive keys and bound oversized values so a
     * careless caller can never persist credentials or tokens.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function sanitize(array $metadata): array
    {
        $clean = [];

        foreach ($metadata as $key => $value) {
            $name = strtolower((string) $key);

            foreach (self::SENSITIVE_KEYS as $blocked) {
                if (str_contains($name, $blocked)) {
                    continue 2;
                }
            }

            $clean[$key] = is_array($value)
                ? $this->sanitize($value)
                : $this->scalar($value);
        }

        return $clean;
    }

    private function scalar(mixed $value): string|int|float|bool|null
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            return mb_substr($value, 0, 500);
        }

        return '[unserializable]';
    }
}
