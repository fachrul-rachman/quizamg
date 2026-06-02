<?php

namespace App\Rules;

use App\Services\Discord\DiscordWebhookUrlParser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DiscordWebhookUrlsRule implements ValidationRule
{
    public function __construct(
        private readonly int $maxUrls = DiscordWebhookUrlParser::MAX_URLS
    ) {
    }

    /**
     * @param  mixed  $value
     */
    public function validate(string $attribute, $value, Closure $fail): void
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return;
        }

        $parts = array_map('trim', explode('|', $raw));
        $parts = array_values(array_filter($parts, fn ($v) => $v !== ''));

        if (count($parts) > $this->maxUrls) {
            $fail("Maksimal {$this->maxUrls} Discord webhook URL (pisahkan dengan |).");
            return;
        }

        foreach ($parts as $url) {
            if (! DiscordWebhookUrlParser::isValidDiscordWebhookUrl($url)) {
                $fail('Format Discord webhook URL tidak valid. Gunakan https://discord.com/api/webhooks/... dan pisahkan dengan |.');
                return;
            }
        }
    }
}

