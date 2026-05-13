<?php

namespace App\Services\GoogleDrive;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleDriveOAuthTokenService
{
    public function getAccessToken(): ?string
    {
        $token = $this->readTokenPayload();
        if (! is_array($token)) {
            Log::warning('google drive oauth token missing');
            return null;
        }

        $accessToken = (string) ($token['access_token'] ?? '');
        $expiresAt = (int) ($token['expires_at'] ?? 0);
        if ($accessToken !== '' && $expiresAt > (time() + 60)) {
            return $accessToken;
        }

        $refreshToken = (string) ($token['refresh_token'] ?? '');

        return $this->refreshAccessToken($refreshToken, $token);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function storeTokenPayload(array $payload): void
    {
        $this->writeTokenPayload($payload);
    }

    public function tokenFileExists(): bool
    {
        return Storage::disk('local')->exists($this->tokenStoragePath());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function readTokenPayload(): ?array
    {
        $path = $this->tokenStoragePath();
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $raw = Storage::disk('local')->get($path);
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $payload = json_decode($raw, true);

        return is_array($payload) ? $payload : null;
    }

    /**
     * @param  array<string, mixed>  $existing
     */
    public function refreshAccessToken(string $refreshToken, array $existing = []): ?string
    {
        if ($refreshToken === '') {
            Log::warning('google drive oauth refresh token missing');
            return null;
        }

        $clientId = (string) env('GOOGLE_DRIVE_OAUTH_CLIENT_ID', '');
        $clientSecret = (string) env('GOOGLE_DRIVE_OAUTH_CLIENT_SECRET', '');
        $tokenUri = (string) env('GOOGLE_DRIVE_OAUTH_TOKEN_URI', 'https://oauth2.googleapis.com/token');

        if ($clientId === '' || $clientSecret === '') {
            Log::warning('google drive oauth client credentials incomplete');
            return null;
        }

        try {
            $resp = Http::asForm()
                ->timeout(20)
                ->post($tokenUri, [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                ]);
        } catch (\Throwable $e) {
            Log::error('google drive oauth refresh request failed', ['error' => $e->getMessage()]);
            return null;
        }

        if (! $resp->successful()) {
            Log::error('google drive oauth refresh response not successful', [
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);
            return null;
        }

        $accessToken = (string) ($resp->json('access_token') ?? '');
        if ($accessToken === '') {
            Log::error('google drive oauth refresh missing access token', ['body' => $resp->body()]);
            return null;
        }

        $expiresIn = (int) ($resp->json('expires_in') ?? 3600);
        $payload = array_merge($existing, [
            'access_token' => $accessToken,
            'expires_at' => time() + max(60, $expiresIn - 60),
            'refresh_token' => (string) ($resp->json('refresh_token') ?? $refreshToken),
            'updated_at' => now()->toIso8601String(),
        ]);

        $this->writeTokenPayload($payload);

        return $accessToken;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeTokenPayload(array $payload): void
    {
        Storage::disk('local')->put(
            $this->tokenStoragePath(),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function tokenStoragePath(): string
    {
        return trim((string) env('GOOGLE_DRIVE_OAUTH_TOKEN_PATH', 'google-drive/oauth-token.json'), '/');
    }
}
