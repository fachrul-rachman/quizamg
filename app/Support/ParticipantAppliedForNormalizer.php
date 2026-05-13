<?php

namespace App\Support;

final class ParticipantAppliedForNormalizer
{
    /**
     * Normalize participant applied-for text for consistent filtering/grouping.
     *
     * Goals:
     * - Trim + collapse whitespace
     * - Title Case by default
     * - Preserve common acronyms (e.g., IT, HR, UI/UX)
     */
    public static function normalize(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        if ($value === '') {
            return '';
        }

        $words = explode(' ', $value);
        $out = [];

        foreach ($words as $word) {
            $out[] = self::normalizeToken($word);
        }

        return trim(implode(' ', $out));
    }

    private static function normalizeToken(string $token): string
    {
        $parts = preg_split('/([\/\-])/u', $token, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (! is_array($parts)) {
            $parts = [$token];
        }

        $out = '';
        foreach ($parts as $part) {
            if ($part === '/' || $part === '-') {
                $out .= $part;
                continue;
            }

            $out .= self::normalizeSegment($part);
        }

        return $out;
    }

    private static function normalizeSegment(string $segment): string
    {
        $segment = trim($segment);
        if ($segment === '') {
            return '';
        }

        $upper = mb_strtoupper($segment);
        if (in_array($upper, self::knownAcronyms(), true)) {
            return $upper;
        }

        $lower = mb_strtolower($segment);
        return mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * @return array<int, string>
     */
    private static function knownAcronyms(): array
    {
        return [
            'IT',
            'HR',
            'QA',
            'UI',
            'UX',
            'UI/UX',
            'R&D',
        ];
    }
}

