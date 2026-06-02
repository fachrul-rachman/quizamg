<?php

namespace App\Services\Discord;

final class DiscordWebhookUrlParser
{
    public const int MAX_URLS = 5;
    public const string PREFIX = 'https://discord.com/api/webhooks/';

    /**
     * Parse a raw string containing 0..N Discord webhook URLs separated by "|".
     *
     * - Trims each item
     * - Drops empty items
     * - Keeps only valid https://discord.com/api/webhooks/... URLs
     * - Uniques and caps at MAX_URLS (or $maxUrls)
     *
     * @return array<int, string>
     */
    public static function parseList(?string $raw, int $maxUrls = self::MAX_URLS): array
    {
        $raw = (string) $raw;
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $items = array_map('trim', explode('|', $raw));
        $items = array_values(array_filter($items, fn ($v) => $v !== ''));

        $valid = [];
        foreach ($items as $url) {
            if (! self::isValidDiscordWebhookUrl($url)) {
                continue;
            }
            $valid[] = $url;
        }

        $valid = array_values(array_unique($valid));

        if ($maxUrls < 1) {
            return [];
        }

        return array_slice($valid, 0, $maxUrls);
    }

    public static function isValidDiscordWebhookUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        if (! str_starts_with($url, self::PREFIX)) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

