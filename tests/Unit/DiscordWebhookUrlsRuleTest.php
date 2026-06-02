<?php

use App\Rules\DiscordWebhookUrlsRule;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

it('accepts a single discord webhook url', function () {
    $v = Validator::make(
        ['discord_webhook_url' => 'https://discord.com/api/webhooks/aaa/bbb'],
        ['discord_webhook_url' => ['nullable', new DiscordWebhookUrlsRule()]],
    );

    expect($v->fails())->toBeFalse();
});

it('accepts pipe-separated discord webhook urls (max 5)', function () {
    $v = Validator::make(
        [
            'discord_webhook_url' => implode('|', [
                'https://discord.com/api/webhooks/1/a',
                'https://discord.com/api/webhooks/2/b',
                'https://discord.com/api/webhooks/3/c',
                'https://discord.com/api/webhooks/4/d',
                'https://discord.com/api/webhooks/5/e',
            ]),
        ],
        ['discord_webhook_url' => ['nullable', new DiscordWebhookUrlsRule()]],
    );

    expect($v->fails())->toBeFalse();
});

it('rejects more than 5 urls', function () {
    $v = Validator::make(
        [
            'discord_webhook_url' => implode('|', [
                'https://discord.com/api/webhooks/1/a',
                'https://discord.com/api/webhooks/2/b',
                'https://discord.com/api/webhooks/3/c',
                'https://discord.com/api/webhooks/4/d',
                'https://discord.com/api/webhooks/5/e',
                'https://discord.com/api/webhooks/6/f',
            ]),
        ],
        ['discord_webhook_url' => ['nullable', new DiscordWebhookUrlsRule()]],
    );

    expect($v->fails())->toBeTrue();
    expect($v->errors()->first('discord_webhook_url'))->toContain('Maksimal');
});

it('rejects non-discord urls', function () {
    $v = Validator::make(
        ['discord_webhook_url' => 'https://example.com|https://discord.com/api/webhooks/ok/ok'],
        ['discord_webhook_url' => ['nullable', new DiscordWebhookUrlsRule()]],
    );

    expect($v->fails())->toBeTrue();
});
