<?php

namespace App\Services\GoogleDrive;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriveFolderService
{
    public function __construct(
        private readonly GoogleDriveAccessTokenService $tokenService,
    ) {
    }

    /**
     * @return array{folder_id:string, google_drive_url:string}|null
     */
    public function createFolder(string $name, string $parentFolderId): ?array
    {
        $name = trim($name);
        if ($name === '' || $parentFolderId === '') {
            return null;
        }

        $token = $this->tokenService->getAccessToken();
        if (! $token) {
            return null;
        }

        $payload = [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentFolderId],
        ];

        try {
            $resp = Http::withToken($token)
                ->timeout(30)
                ->withOptions([
                    'query' => [
                        'supportsAllDrives' => 'true',
                        'fields' => 'id,webViewLink',
                    ],
                ])
                ->post('https://www.googleapis.com/drive/v3/files', $payload);
        } catch (\Throwable $e) {
            Log::error('google drive folder create request failed', ['error' => $e->getMessage()]);
            return null;
        }

        if (! $resp->successful()) {
            Log::error('google drive folder create response not successful', [
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);
            return null;
        }

        $folderId = (string) ($resp->json('id') ?? '');
        $webViewLink = (string) ($resp->json('webViewLink') ?? '');
        if ($folderId === '') {
            Log::error('google drive folder create missing folder id', ['body' => $resp->body()]);
            return null;
        }

        $permissionOk = $this->setAnyoneReaderPermission($token, $folderId);
        if (! $permissionOk) {
            Log::warning('google drive folder permission set failed', ['folder_id' => $folderId]);
        }

        $url = $webViewLink !== '' ? $webViewLink : ('https://drive.google.com/drive/folders/'.$folderId);

        return [
            'folder_id' => $folderId,
            'google_drive_url' => $url,
        ];
    }

    private function setAnyoneReaderPermission(string $token, string $fileId): bool
    {
        try {
            $resp = Http::withToken($token)
                ->timeout(30)
                ->withOptions([
                    'query' => [
                        'supportsAllDrives' => 'true',
                    ],
                ])
                ->post('https://www.googleapis.com/drive/v3/files/'.$fileId.'/permissions', [
                    'type' => 'anyone',
                    'role' => 'reader',
                    'allowFileDiscovery' => false,
                ]);
        } catch (\Throwable $e) {
            Log::error('google drive folder permission request failed', ['error' => $e->getMessage()]);
            return false;
        }

        if (! $resp->successful()) {
            Log::error('google drive folder permission response not successful', [
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);
            return false;
        }

        return true;
    }
}
