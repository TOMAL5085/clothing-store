<?php

namespace App\Support;

class Money
{
    public static function toMinor(int|float|string $amount): int
    {
        $normalized = number_format((float) $amount, 2, '.', '');

        return (int) round(((float) $normalized) * 100);
    }

    public static function fromMinor(int $minor): string
    {
        return number_format($minor / 100, 2, '.', '');
    }
}
