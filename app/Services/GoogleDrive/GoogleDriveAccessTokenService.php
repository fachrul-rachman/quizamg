<?php

namespace App\Services\GoogleDrive;

class GoogleDriveAccessTokenService
{
    public function __construct(
        private readonly GoogleDriveOAuthTokenService $oauthTokenService,
        private readonly GoogleServiceAccountTokenService $serviceAccountTokenService,
    ) {
    }

    public function getAccessToken(): ?string
    {
        return $this->authMode() === 'oauth'
            ? $this->oauthTokenService->getAccessToken()
            : $this->serviceAccountTokenService->getAccessToken();
    }

    private function authMode(): string
    {
        return strtolower(trim((string) env('GOOGLE_DRIVE_AUTH_MODE', 'service_account')));
    }
}
