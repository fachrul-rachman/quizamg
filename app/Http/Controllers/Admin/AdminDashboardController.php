<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoogleDrive\GoogleDriveOAuthTokenService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function __invoke(GoogleDriveOAuthTokenService $googleDriveOAuthTokenService): View
    {
        $user = request()->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');
        $userId = (int) ($user?->id ?? 0);

        $tz = 'Asia/Jakarta';
        $todayStart = CarbonImmutable::now($tz)->startOfDay();
        $todayEnd = CarbonImmutable::now($tz)->endOfDay();

        $stats = [
            'total_quizzes' => $isSuperAdmin
                ? DB::table('quizzes')->count()
                : DB::table('quizzes')->where('created_by', $userId)->count(),
            'total_links' => $isSuperAdmin
                ? DB::table('quiz_links')->count()
                : DB::table('quiz_links')
                    ->join('quizzes', 'quizzes.id', '=', 'quiz_links.quiz_id')
                    ->where('quizzes.created_by', $userId)
                    ->count(),
            'total_results' => $isSuperAdmin
                ? DB::table('quiz_results')->count()
                : DB::table('quiz_results')
                    ->join('quizzes', 'quizzes.id', '=', 'quiz_results.quiz_id')
                    ->where('quizzes.created_by', $userId)
                    ->count(),
            'total_admin_users' => $isSuperAdmin ? User::count() : 0,
        ];

        $googleDrive = [
            'enabled' => filter_var(env('GOOGLE_DRIVE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'auth_mode' => strtolower(trim((string) env('GOOGLE_DRIVE_AUTH_MODE', 'service_account'))),
            'folder_id' => (string) env('GOOGLE_DRIVE_FOLDER_ID', ''),
            'oauth_connected' => $googleDriveOAuthTokenService->getAccessToken() !== null,
        ];

        $latestResults = DB::table('quiz_results')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_results.quiz_id')
            ->join('quiz_attempts', 'quiz_attempts.id', '=', 'quiz_results.quiz_attempt_id')
            ->when(! $isSuperAdmin, fn ($q) => $q->where('quizzes.created_by', $userId))
            ->whereBetween('quiz_results.calculated_at', [$todayStart, $todayEnd])
            ->select([
                'quiz_results.id',
                'quizzes.title as quiz_title',
                'quiz_attempts.participant_name',
                'quiz_attempts.participant_applied_for',
                'quiz_results.score_percentage',
                'quiz_results.grade_letter',
                'quiz_results.grade_label',
                'quiz_results.result_status',
                'quiz_results.calculated_at',
            ])
            ->orderByDesc('quiz_results.calculated_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.dashboard', [
            'stats' => $stats,
            'latestResults' => $latestResults,
            'googleDrive' => $googleDrive,
        ]);
    }
}
