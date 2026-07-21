<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Throwable;

final class ParticipantWorkStartFormatter
{
    public static function format(DateTimeInterface|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        try {
            return CarbonImmutable::parse($value)
                ->locale('id')
                ->translatedFormat('F Y');
        } catch (Throwable) {
            return '-';
        }
    }
}
