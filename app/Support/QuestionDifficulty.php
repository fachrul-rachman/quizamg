<?php

namespace App\Support;

class QuestionDifficulty
{
    public const DEFAULT = 'mudah';

    public const LEVELS = [
        'mudah',
        'sedang',
        'sulit',
        'sangat_sulit',
    ];

    public const LABELS = [
        'mudah' => 'Mudah',
        'sedang' => 'Sedang',
        'sulit' => 'Sulit',
        'sangat_sulit' => 'Sangat Sulit',
    ];

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return self::LABELS;
    }

    public static function normalize(mixed $value): ?string
    {
        $text = mb_strtolower(trim((string) ($value ?? '')));
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        if ($text === '') {
            return self::DEFAULT;
        }

        return match ($text) {
            'mudah' => 'mudah',
            'sedang' => 'sedang',
            'sulit' => 'sulit',
            'sangat sulit' => 'sangat_sulit',
            'sangat_sulit' => 'sangat_sulit',
            default => null,
        };
    }

    public static function label(?string $level): string
    {
        return self::LABELS[$level ?? ''] ?? self::LABELS[self::DEFAULT];
    }

    public static function sortRank(?string $level): int
    {
        $index = array_search($level, self::LEVELS, true);

        return is_int($index) ? $index : 0;
    }

    public static function isValid(?string $level): bool
    {
        return in_array($level, self::LEVELS, true);
    }
}
