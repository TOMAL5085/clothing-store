<?php

namespace App\Services\Marketing;

use App\Models\CancellationRequest;
use App\Models\MarketingAttribution;
use App\Models\MarketingEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MarketingEventService
{
    public function __construct(
        private readonly MarketingEventDispatcher $dispatcher,
    ) {}

    /**
     * Resolve effective consent. An authenticated user's stored preference
     * wins when present; otherwise the point-in-time snapshot travels with
     * the event. Absent consent is denied, never assumed.
     *
     * @return array{analytics:bool, marketing:bool}
     */
    public static function resolveConsent(?User $user, ?array $snapshot): array
    {
        $stored = is_array($user?->marketing_consent) ? $user->marketing_consent : null;

        if ($stored !== null) {
            return [
                'analytics' => (bool) ($stored['analytics'] ?? false),
                'marketing' => (bool) ($stored['marketing'] ?? false),
            ];
        }

        return [
            'analytics' => (bool) ($snapshot['analytics'] ?? false),
            'marketing' => (bool) ($snapshot['marketing'] ?? false),
        ];
    }

    public static function consentState(array $consent): string
    {
        return match (true) {
            $consent['analytics'] && $consent['marketing'] => 'granted',
            $consent['analytics'] => 'analytics',
            $consent['marketing'] => 'marketing',
            default => 'denied',
        };
    }

    /**
     * Ingest one client-originated event. Returns [event, status] where
     * status is created|duplicate|declined. Declined (consent denied)
     * persists nothing and never throws.
     *
     * @param  array<string, mixed>  $input  Validated request payload.
     * @return array{event:?MarketingEvent, status:string}
     */
    public function ingest(?User $user, array $input): array
    {
        $name = $input['event_name'];
        $eventId = $input['event_id'] ?? (string) Str::uuid();

        // Idempotency first: a retried submission returns the original row,
        // but only to its owner.
        $existing = MarketingEvent::query()->where('event_id', $eventId)->first();

        if ($existing) {
            if (! $existing->belongsToIdentity($user, $input['anonymous_id'] ?? null)) {
                throw new NotFoundHttpException;
            }

            return ['event' => $existing, 'status' => 'duplicate'];
        }

        $input = $this->normalizeClientInput($input);

        $consent = self::resolveConsent($user, $input['consent'] ?? null);

        if (! $consent['analytics']) {
            return ['event' => null, 'status' => 'declined'];
        }

        if ($user === null && empty($input['anonymous_id'])) {
            throw ValidationException::withMessages([
                'anonymous_id' => 'Anonymous events require an anonymous identifier.',
            ]);
        }

        $attribution = $this->attribute($user, $input);

        $event = MarketingEvent::query()->create([
            'event_id' => $eventId,
            'event_name' => $name,
            'event_source' => 'client',
            'user_id' => $user?->id,
            'anonymous_id' => $input['anonymous_id'] ?? null,
            'session_id' => $input['session_id'] ?? null,
            'product_external_id' => $input['product_external_id'] ?? null,
            'occurred_at' => $input['occurred_at'] ?? now(),
            'currency' => $input['currency'] ?? null,
            'value' => $input['value'] ?? null,
            'attribution_id' => $attribution?->id,
            'metadata' => $input['metadata'] ?? [],
            'consent_state' => self::consentState($consent),
        ]);

        $this->dispatcher->fanout($event);

        return ['event' => $event, 'status' => 'created'];
    }

    /**
     * Server-side semantic validation for client-originated events. The
     * FormRequest only checks shapes; everything trust-sensitive happens
     * here: catalog authority, metadata allowlists, size caps, identifier
     * formats, timestamp sanity, currency/value rules, and product
     * existence. Unknown server-only names are rejected outright.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalizeClientInput(array $input): array
    {
        $name = $input['event_name'];

        if (! array_key_exists($name, MarketingEventCatalog::EVENTS)) {
            throw ValidationException::withMessages(['event_name' => 'Unsupported event.']);
        }

        if (MarketingEventCatalog::isServerOnly($name)) {
            throw ValidationException::withMessages(['event_name' => 'This event is recorded server-side only.']);
        }

        $allowed = MarketingEventCatalog::metadataKeys($name);
        $metadata = [];

        foreach (is_array($input['metadata'] ?? null) ? $input['metadata'] : [] as $key => $value) {
            if (! in_array($key, $allowed, true)) {
                continue;
            }

            $normalized = $this->normalizeMetadataValue($key, $value);

            if ($normalized !== null) {
                $metadata[$key] = $normalized;
            }
        }

        if (strlen((string) json_encode($metadata)) > 4096) {
            throw ValidationException::withMessages(['metadata' => 'Metadata is too large.']);
        }

        $input['metadata'] = $metadata;

        foreach (['anonymous_id', 'session_id'] as $key) {
            if (isset($input[$key]) && ! preg_match('/^[A-Za-z0-9_\-]{1,64}$/', (string) $input[$key])) {
                throw ValidationException::withMessages([$key => 'Invalid identifier format.']);
            }
        }

        if (isset($input['occurred_at'])) {
            try {
                $occurredAt = Carbon::parse($input['occurred_at']);
            } catch (\Throwable) {
                throw ValidationException::withMessages(['occurred_at' => 'Invalid timestamp.']);
            }

            if ($occurredAt->isAfter(now()->addMinutes(5))) {
                $input['occurred_at'] = now();
            } elseif ($occurredAt->isBefore(now()->subDays(30))) {
                throw ValidationException::withMessages(['occurred_at' => 'Event is too old.']);
            } else {
                $input['occurred_at'] = $occurredAt;
            }
        }

        if (isset($input['product_external_id'])) {
            $externalId = mb_substr(trim((string) $input['product_external_id']), 0, 120);

            if (! Product::query()->where('external_id', $externalId)->exists()) {
                throw ValidationException::withMessages(['product_external_id' => 'Unknown product.']);
            }

            $input['product_external_id'] = $externalId;
        }

        if (isset($input['currency'])) {
            $currency = strtoupper(trim((string) $input['currency']));

            if (! preg_match('/^[A-Z]{3}$/', $currency)) {
                throw ValidationException::withMessages(['currency' => 'Invalid currency code.']);
            }

            $input['currency'] = $currency;
        }

        if (array_key_exists('value', $input)) {
            $allowsValue = MarketingEventCatalog::EVENTS[$name]['value'] ?? false;
            $value = is_numeric($input['value'] ?? null) ? (float) $input['value'] : null;

            $input['value'] = ($allowsValue && $value !== null && $value >= 0)
                ? round($value, 2)
                : null;
        }

        if (isset($input['consent']) && is_array($input['consent'])) {
            $input['consent'] = [
                'analytics' => (bool) ($input['consent']['analytics'] ?? false),
                'marketing' => (bool) ($input['consent']['marketing'] ?? false),
            ];
        }

        return $input;
    }

    private function normalizeMetadataValue(string $key, mixed $value): mixed
    {
        return match ($key) {
            'path' => $this->normalizePath($value),
            'query' => is_string($value) && trim($value) !== ''
                ? mb_substr(trim($value), 0, 200)
                : null,
            'slug' => is_string($value) && trim($value) !== ''
                ? mb_substr(trim($value), 0, 255)
                : null,
            'category' => is_string($value) && trim($value) !== ''
                ? mb_substr(trim($value), 0, 120)
                : null,
            'currency' => is_string($value) && preg_match('/^[A-Za-z]{3}$/', trim($value))
                ? strtoupper(trim($value))
                : null,
            'product_external_id' => $this->normalizeProductReference($value),
            'quantity', 'item_count', 'result_count' => $this->normalizeCount($value),
            default => null,
        };
    }

    private function normalizePath(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $path = mb_substr(trim($value), 0, 500);

        // Relative application paths only — never full URLs with hosts,
        // credentials, or tokens.
        if (! str_starts_with($path, '/')) {
            return null;
        }

        return $path;
    }

    private function normalizeProductReference(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $externalId = mb_substr(trim($value), 0, 120);

        return Product::query()->where('external_id', $externalId)->exists()
            ? $externalId
            : null;
    }

    private function normalizeCount(mixed $value): ?int
    {
        if (is_int($value) && $value >= 0 && $value <= 1000000) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value) && (int) $value <= 1000000) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Record a server-originated lifecycle event exactly once. The event_id
     * is deterministic per business object so webhook repeats, worker
     * retries, and confirmation refreshes can never duplicate it. Returns
     * null when measurement is disabled via config.
     */
    public function recordServerEvent(string $eventId, string $name, array $attributes = []): ?MarketingEvent
    {
        if (! config('marketing.events_enabled', true)) {
            return null;
        }

        $event = MarketingEvent::query()->firstOrCreate(
            ['event_id' => $eventId],
            array_merge([
                'event_name' => $name,
                'event_source' => 'server',
                'occurred_at' => now(),
                'consent_state' => 'necessary',
                'metadata' => [],
            ], $attributes)
        );

        if ($event->wasRecentlyCreated) {
            $this->dispatcher->fanout($event);
        }

        return $event;
    }

    public function recordPurchase(Order $order): ?MarketingEvent
    {
        $order->loadMissing('items');

        return $this->recordServerEvent(
            "purchase:{$order->id}",
            'purchase',
            [
                'user_id' => $order->user_id,
                'anonymous_id' => $order->anonymous_id,
                'order_id' => $order->id,
                'order_number' => $order->number,
                'currency' => $order->currency,
                'value' => $order->total,
                'attribution_id' => $this->attributionForOwner($order->user_id, $order->anonymous_id)?->id,
                'metadata' => [
                    'lines' => $order->items->map(fn ($item) => [
                        'product_external_id' => $item->product_external_id,
                        'slug' => $item->product_slug,
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                    ])->values()->all(),
                ],
            ]
        );
    }

    public function recordPaymentFailed(Order $order): ?MarketingEvent
    {
        return $this->recordServerEvent(
            "payment_failed:{$order->id}",
            'payment_failed',
            [
                'user_id' => $order->user_id,
                'anonymous_id' => $order->anonymous_id,
                'order_id' => $order->id,
                'order_number' => $order->number,
                'currency' => $order->currency,
                'value' => $order->total,
                'attribution_id' => $this->attributionForOwner($order->user_id, $order->anonymous_id)?->id,
            ]
        );
    }

    public function recordOrderCancelled(CancellationRequest $cancellation): ?MarketingEvent
    {
        $order = $cancellation->order;

        return $this->recordServerEvent(
            "order_cancelled:{$cancellation->id}",
            'order_cancelled',
            [
                'user_id' => $order->user_id,
                'anonymous_id' => $order->anonymous_id,
                'order_id' => $order->id,
                'order_number' => $order->number,
                'currency' => $order->currency,
                'value' => $order->total,
                'attribution_id' => $this->attributionForOwner($order->user_id, $order->anonymous_id)?->id,
            ]
        );
    }

    public function recordRefundCompleted(Refund $refund): ?MarketingEvent
    {
        $order = $refund->order;

        return $this->recordServerEvent(
            "refund_completed:{$refund->id}",
            'refund_completed',
            [
                'user_id' => $order->user_id,
                'anonymous_id' => $order->anonymous_id,
                'order_id' => $order->id,
                'order_number' => $order->number,
                'currency' => $refund->currency ?? $order->currency,
                'value' => $refund->amount,
                'attribution_id' => $this->attributionForOwner($order->user_id, $order->anonymous_id)?->id,
            ]
        );
    }

    /**
     * Upsert first-party attribution from a sanitized payload. Rows are
     * keyed by authenticated user when known, else by anonymous identity.
     * Existing rows only gain a user link when currently anonymous — two
     * users are never merged. Touchpoints without marketing parameters
     * only refresh last_seen_at on an existing row; they never create one.
     *
     * @param  array<string, mixed>  $payload
     */
    public function attribute(?User $user, array $payload): ?MarketingAttribution
    {
        $anonymousId = $payload['anonymous_id'] ?? null;
        $sessionId = $payload['session_id'] ?? null;
        $attribution = $payload['attribution'] ?? null;

        $params = is_array($attribution) ? $this->sanitizeAttribution($attribution) : [];

        $row = null;

        if ($user) {
            $row = MarketingAttribution::query()->where('user_id', $user->id)->first();
        }

        if (! $row && is_string($anonymousId) && $anonymousId !== '') {
            $row = MarketingAttribution::query()->where('anonymous_id', $anonymousId)->first();

            if ($row && $user && $row->user_id === null) {
                $row->update(['user_id' => $user->id]);
            }
        }

        if (! $row) {
            if ($params === []) {
                return null;
            }

            $row = MarketingAttribution::query()->create(array_merge([
                'anonymous_id' => is_string($anonymousId) && $anonymousId !== '' ? $anonymousId : null,
                'user_id' => $user?->id,
                'session_id' => is_string($sessionId) ? $sessionId : null,
                'first_seen_at' => now(),
            ], $params, [
                'first_source' => $params['source'] ?? null,
                'first_medium' => $params['medium'] ?? null,
                'first_campaign' => $params['campaign'] ?? null,
            ]));
        } elseif ($params !== []) {
            $row->update(array_merge($params, [
                'session_id' => is_string($sessionId) ? $sessionId : $row->session_id,
            ]));
        }

        $row->update(['last_seen_at' => now()]);

        return $row->fresh();
    }

    /**
     * @param  array<string, mixed>  $attribution
     * @return array<string, mixed>
     */
    public function sanitizeAttribution(array $attribution): array
    {
        $text = fn ($value, int $max) => is_string($value) && $value !== ''
            ? mb_substr(trim($value), 0, $max)
            : null;

        $params = array_filter([
            'source' => $text($attribution['utm_source'] ?? null, 120),
            'medium' => $text($attribution['utm_medium'] ?? null, 120),
            'campaign' => $text($attribution['utm_campaign'] ?? null, 200),
            'term' => $text($attribution['utm_term'] ?? null, 200),
            'content' => $text($attribution['utm_content'] ?? null, 200),
            'landing_url' => $this->sanitizeUrl($attribution['landing_url'] ?? null),
            'referrer' => $this->sanitizeUrl($attribution['referrer'] ?? null),
        ], fn ($value) => $value !== null);

        $clickIds = [];

        foreach (['gclid' => 200, 'gbraid' => 200, 'wbraid' => 200, 'fbclid' => 200] as $key => $max) {
            $value = $text($attribution[$key] ?? null, $max);

            if ($value !== null && preg_match('/^[A-Za-z0-9_\-]+$/', $value)) {
                $clickIds[$key] = $value;
            }
        }

        if ($clickIds !== []) {
            $params['click_ids'] = $clickIds;
        }

        return $params;
    }

    private function sanitizeUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $url = trim(mb_substr($url, 0, 1000));

        if (! preg_match('#^https?://#i', $url)) {
            return null;
        }

        // Strip any embedded credentials or fragments before persisting.
        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $clean = strtolower($parts['scheme']).'://'.$parts['host']
            .(! empty($parts['port']) ? ':'.$parts['port'] : '')
            .($parts['path'] ?? '');

        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
            $allowed = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

            $kept = array_intersect_key($query, array_flip($allowed));

            if ($kept !== []) {
                $clean .= '?'.http_build_query($kept);
            }
        }

        return $clean;
    }

    private function attributionForOwner(?int $userId, ?string $anonymousId): ?MarketingAttribution
    {
        if ($userId) {
            $row = MarketingAttribution::query()->where('user_id', $userId)->first();

            if ($row) {
                return $row;
            }
        }

        if (is_string($anonymousId) && $anonymousId !== '') {
            return MarketingAttribution::query()->where('anonymous_id', $anonymousId)->first();
        }

        return null;
    }
}
