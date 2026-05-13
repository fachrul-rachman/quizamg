<?php

namespace App\Services\GoogleDrive;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriveUploadService
{
    public function __construct(
        private readonly GoogleDriveAccessTokenService $tokenService,
    ) {
    }

    /**
     * @return array{file_id:string, google_drive_url:string}|null
     */
    public function uploadPdf(string $absoluteFilePath, string $fileName, string $folderId): ?array
    {
        if (! is_file($absoluteFilePath)) {
            Log::warning('google drive upload file missing', ['path' => $absoluteFilePath]);
            return null;
        }

        $token = $this->tokenService->getAccessToken();
        if (! $token) {
            return null;
        }

        $bytes = file_get_contents($absoluteFilePath);
        if (! is_string($bytes) || $bytes === '') {
            Log::warning('google drive upload file unreadable', ['path' => $absoluteFilePath]);
            return null;
        }

        $metadata = [
            'name' => $fileName,
            'parents' => [$folderId],
        ];

        $boundary = '-------drive-boundary-'.bin2hex(random_bytes(12));
        $eol = "\r\n";

        $body = '';
        $body .= '--'.$boundary.$eol;
        $body .= 'Content-Type: application/json; charset=UTF-8'.$eol.$eol;
        $body .= json_encode($metadata, JSON_UNESCAPED_SLASHES).$eol;
        $body .= '--'.$boundary.$eol;
        $body .= 'Content-Type: application/pdf'.$eol.$eol;
        $body .= $bytes.$eol;
        $body .= '--'.$boundary.'--'.$eol;

        try {
            $resp = Http::withToken($token)
                ->withHeaders([
                    'Content-Type' => 'multipart/related; boundary='.$boundary,
                ])
                ->timeout(60)
                ->send('POST', 'https://www.googleapis.com/upload/drive/v3/files', [
                    'query' => [
                        'uploadType' => 'multipart',
                        'supportsAllDrives' => 'true',
                        'fields' => 'id,webViewLink',
                    ],
                    'body' => $body,
                ]);
        } catch (\Throwable $e) {
            Log::error('google drive upload request failed', ['error' => $e->getMessage()]);
            return null;
        }

        if (! $resp->successful()) {
            Log::error('google drive upload response not successful', [
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);
            return null;
        }

        $fileId = (string) ($resp->json('id') ?? '');
        $webViewLink = (string) ($resp->json('webViewLink') ?? '');
        if ($fileId === '') {
            Log::error('google drive upload missing file id', ['body' => $resp->body()]);
            return null;
        }

        $permissionOk = $this->setAnyoneReaderPermission($token, $fileId);
        if (! $permissionOk) {
            Log::warning('google drive permission set failed', ['file_id' => $fileId]);
        }

        $url = $webViewLink !== '' ? $webViewLink : ('https://drive.google.com/file/d/'.$fileId.'/view?usp=sharing');

        return [
            'file_id' => $fileId,
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
            Log::error('google drive permission request failed', ['error' => $e->getMessage()]);
            return false;
        }

        if (! $resp->successful()) {
            Log::error('google drive permission response not successful', [
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);
            return false;
        }

        return true;
    }
}
