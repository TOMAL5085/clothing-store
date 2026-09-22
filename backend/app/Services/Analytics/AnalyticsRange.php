<?php

namespace App\Services\Analytics;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class AnalyticsRange
{
    public const PRESETS = ['today', '7d', '30d', 'month', 'prev_month', 'year', 'custom'];

    public const GROUPS = ['auto', 'day', 'week', 'month'];

    private function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly string $preset,
        public readonly string $group,
    ) {}

    /**
     * @param  array{preset?:string, from?:string, to?:string, group?:string}  $input
     */
    public static function fromInput(array $input): self
    {
        $timezone = config('app.timezone', 'UTC');
        $now = CarbonImmutable::now($timezone);
        $preset = $input['preset'] ?? '30d';

        [$from, $to] = match ($preset) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            '7d' => [$now->subDays(6)->startOfDay(), $now->endOfDay()],
            '30d' => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
            'month' => [$now->startOfMonth(), $now->endOfMonth()],
            'prev_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            'year' => [$now->startOfYear(), $now->endOfYear()],
            'custom' => [
                CarbonImmutable::parse($input['from'], $timezone)->startOfDay(),
                CarbonImmutable::parse($input['to'], $timezone)->endOfDay(),
            ],
            default => throw new InvalidArgumentException("Unsupported analytics preset [{$preset}]."),
        };

        $group = $input['group'] ?? 'auto';

        return new self($from, $to, $preset, $group === 'auto' ? self::autoGroup($from, $to) : $group);
    }

    public static function autoGroup(CarbonImmutable $from, CarbonImmutable $to): string
    {
        $days = $from->diffInDays($to) + 1;

        if ($days <= 62) {
            return 'day';
        }

        if ($days <= 370) {
            return 'week';
        }

        return 'month';
    }

    /**
     * The equivalent-length period immediately before this range, used for
     * like-for-like comparison. Calendar presets compare against the previous
     * calendar unit; rolling/custom ranges compare against the preceding span.
     */
    public function previous(): self
    {
        $timezone = config('app.timezone', 'UTC');

        if ($this->preset === 'month' || $this->preset === 'prev_month') {
            $start = $this->from->subMonthNoOverflow()->startOfMonth();

            return new self($start, $start->endOfMonth(), $this->preset, $this->group);
        }

        if ($this->preset === 'year') {
            $start = $this->from->subYearNoOverflow()->startOfYear();

            return new self($start, $start->endOfYear(), $this->preset, $this->group);
        }

        $days = $this->from->diffInDays($this->to) + 1;
        $to = $this->from->subSecond();
        $from = $to->subDays($days - 1)->startOfDay();

        return new self($from, $to->endOfDay(), $this->preset, $this->group);
    }

    /**
     * @return array{preset:string, from:string, to:string, timezone:string, group:string}
     */
    public function metadata(): array
    {
        return [
            'preset' => $this->preset,
            'from' => $this->from->toIso8601String(),
            'to' => $this->to->toIso8601String(),
            'timezone' => config('app.timezone', 'UTC'),
            'group' => $this->group,
        ];
    }

    /**
     * Chronological bucket start dates (Y-m-d) covering the whole range.
     *
     * @return list<string>
     */
    public function buckets(): array
    {
        $buckets = [];
        $cursor = match ($this->group) {
            'month' => $this->from->startOfMonth(),
            'week' => $this->from->startOfWeek(),
            default => $this->from->startOfDay(),
        };

        while ($cursor->lessThanOrEqualTo($this->to)) {
            $buckets[] = $cursor->toDateString();
            $cursor = match ($this->group) {
                'month' => $cursor->addMonthNoOverflow()->startOfMonth(),
                'week' => $cursor->addWeek()->startOfWeek(),
                default => $cursor->addDay()->startOfDay(),
            };
        }

        return $buckets;
    }

    public function bucketExpression(string $column): string
    {
        return match ($this->group) {
            'month' => "date_trunc('month', {$column})::date",
            'week' => "date_trunc('week', {$column})::date",
            default => "{$column}::date",
        };
    }
}
