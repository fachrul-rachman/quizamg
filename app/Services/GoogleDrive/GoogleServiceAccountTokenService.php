<?php

namespace App\Services\GoogleDrive;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleServiceAccountTokenService
{
    public function getAccessToken(): ?string
    {
        $cacheKey = 'google_drive_sa_access_token';

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $envEmail = (string) env('GOOGLE_DRIVE_CLIENT_EMAIL', '');
        $envKey = (string) env('GOOGLE_DRIVE_PRIVATE_KEY', '');
        $envTokenUri = (string) env('GOOGLE_DRIVE_TOKEN_URI', '');

        $clientEmail = $envEmail !== '' ? $envEmail : '';
        $privateKey = $envKey !== '' ? $this->normalizePrivateKey($envKey) : '';
        $tokenUri = $envTokenUri !== '' ? $envTokenUri : 'https://oauth2.googleapis.com/token';

        if ($clientEmail === '' || $privateKey === '') {
            $creds = $this->readCredentialsFromJsonPath();
            if (! $creds) {
                return null;
            }

            $clientEmail = (string) ($creds['client_email'] ?? '');
            $privateKey = (string) ($creds['private_key'] ?? '');
            $tokenUri = (string) ($creds['token_uri'] ?? $tokenUri);
        }

        if ($clientEmail === '' || $privateKey === '') {
            Log::warning('google drive service account creds incomplete');
            return null;
        }

        $now = time();
        $claims = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/drive',
            'aud' => $tokenUri,
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $jwt = $this->jwtSign($claims, $privateKey);
        if (! $jwt) {
            return null;
        }

        try {
            $resp = Http::asForm()
                ->timeout(20)
                ->post($tokenUri, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);
        } catch (\Throwable $e) {
            Log::error('google drive token request failed', ['error' => $e->getMessage()]);
            return null;
        }

        if (! $resp->successful()) {
            Log::error('google drive token response not successful', [
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);
            return null;
        }

        $token = $resp->json('access_token');
        $expiresIn = (int) ($resp->json('expires_in') ?? 3600);

        if (! is_string($token) || $token === '') {
            Log::error('google drive token missing in response', ['body' => $resp->body()]);
            return null;
        }

        $ttl = max(60, $expiresIn - 120);
        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readCredentialsFromJsonPath(): ?array
    {
        $jsonPath = (string) env('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON', '');
        if ($jsonPath === '' || ! is_file($jsonPath)) {
            return null;
        }

        $raw = file_get_contents($jsonPath);
        if (! is_string($raw) || $raw === '') {
            Log::warning('google drive service account json unreadable', ['path' => $jsonPath]);
            return null;
        }

        $creds = json_decode($raw, true);
        if (! is_array($creds)) {
            Log::warning('google drive service account json invalid', ['path' => $jsonPath]);
            return null;
        }

        return $creds;
    }

    private function normalizePrivateKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }

        if (str_contains($key, '\\n')) {
            $key = str_replace('\\n', "\n", $key);
        }

        return $key;
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function jwtSign(array $claims, string $privateKeyPem): ?string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];

        $headerB64 = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $claimsB64 = $this->base64UrlEncode(json_encode($claims, JSON_UNESCAPED_SLASHES));

        if ($headerB64 === '' || $claimsB64 === '') {
            return null;
        }

        $unsigned = $headerB64.'.'.$claimsB64;

        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);
        if (! $ok || $signature === '') {
            Log::error('google drive jwt signing failed');
            return null;
        }

        return $unsigned.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $data): string
    {
        if ($data === '') {
            return '';
        }

        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
