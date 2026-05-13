<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoogleDrive\GoogleDriveOAuthTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleDriveOAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = (string) env('GOOGLE_DRIVE_OAUTH_CLIENT_ID', '');
        $redirectUri = (string) env('GOOGLE_DRIVE_OAUTH_REDIRECT_URI', '');

        if ($clientId === '' || $redirectUri === '') {
            return redirect('/admin/dashboard')->with('error', 'Google Drive OAuth belum dikonfigurasi. Periksa client ID dan redirect URI di .env.');
        }

        $state = Str::random(40);
        $request->session()->put('google_drive_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request, GoogleDriveOAuthTokenService $tokenService): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('google_drive_oauth_state', '');
        $incomingState = (string) $request->query('state', '');

        if ($expectedState === '' || ! hash_equals($expectedState, $incomingState)) {
            return redirect('/admin/dashboard')->with('error', 'State OAuth Google Drive tidak valid. Ulangi proses connect dari dashboard.');
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect('/admin/dashboard')->with('error', 'Google Drive tidak mengirim authorization code. Ulangi proses connect lalu pilih akun yang benar.');
        }

        $clientId = (string) env('GOOGLE_DRIVE_OAUTH_CLIENT_ID', '');
        $clientSecret = (string) env('GOOGLE_DRIVE_OAUTH_CLIENT_SECRET', '');
        $redirectUri = (string) env('GOOGLE_DRIVE_OAUTH_REDIRECT_URI', '');
        $tokenUri = (string) env('GOOGLE_DRIVE_OAUTH_TOKEN_URI', 'https://oauth2.googleapis.com/token');

        if ($clientId === '' || $clientSecret === '' || $redirectUri === '') {
            return redirect('/admin/dashboard')->with('error', 'Konfigurasi Google Drive OAuth di .env belum lengkap. Periksa client ID, client secret, dan redirect URI.');
        }

        try {
            $resp = Http::asForm()
                ->timeout(20)
                ->post($tokenUri, [
                    'code' => $code,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                ]);
        } catch (\Throwable $e) {
            Log::error('google drive oauth token exchange failed', ['error' => $e->getMessage()]);

            return redirect('/admin/dashboard')->with('error', 'Gagal menukar authorization code Google Drive. Periksa koneksi dan redirect URI, lalu coba lagi.');
        }

        if (! $resp->successful()) {
            Log::error('google drive oauth token exchange not successful', [
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);

            return redirect('/admin/dashboard')->with('error', 'Google Drive menolak proses OAuth. Periksa client app di Google Console lalu coba connect ulang.');
        }

        $accessToken = (string) ($resp->json('access_token') ?? '');
        $refreshToken = (string) ($resp->json('refresh_token') ?? '');
        $expiresIn = (int) ($resp->json('expires_in') ?? 3600);

        if ($accessToken === '' || $refreshToken === '') {
            return redirect('/admin/dashboard')->with(
                'error',
                'Refresh token Google Drive tidak diterima, cabut akses app Google lalu coba connect ulang.'
            );
        }

        $tokenService->storeTokenPayload([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => time() + max(60, $expiresIn - 60),
            'scope' => (string) ($resp->json('scope') ?? ''),
            'token_type' => (string) ($resp->json('token_type') ?? ''),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect('/admin/dashboard')->with('success', 'Google Drive OAuth berhasil terhubung.');
    }
}
