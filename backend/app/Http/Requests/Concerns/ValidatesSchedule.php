<?php

namespace App\Http\Requests\Concerns;

use Closure;

trait ValidatesSchedule
{
    /**
     * Optional scheduling rules. Either bound may be absent; only a
     * reversed explicit window is rejected.
     *
     * @return array<string, mixed>
     */
    protected function scheduleRules(): array
    {
        $afterStart = function (string $attribute, mixed $value, Closure $fail): void {
            $startsAt = $this->input('starts_at');

            if (! is_string($startsAt) || $startsAt === '') {
                return;
            }

            try {
                $start = new \DateTimeImmutable($startsAt);
                $end = new \DateTimeImmutable((string) $value);
            } catch (\Throwable) {
                return;
            }

            if ($end <= $start) {
                $fail('The :attribute must be after the start date.');
            }
        };

        return [
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', $afterStart],
        ];
    }
}
