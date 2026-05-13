<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizLink;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminGenerateLinkController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');

        $activeQuizzes = Quiz::query()
            ->where('is_active', true)
            ->when(! $isSuperAdmin && $user, fn ($q) => $q->where('created_by', (int) $user->id))
            ->orderBy('title')
            ->get(['id', 'title']);

        $generatedIds = $request->session()->get('generated_link_ids', []);
        $generatedLinks = collect();
        if (is_array($generatedIds) && $generatedIds !== []) {
            $generatedLinks = QuizLink::query()
                ->with('quiz:id,title')
                ->whereIn('id', $generatedIds)
                ->when(! $isSuperAdmin && $user, function ($q) use ($user) {
                    $q->whereHas('quiz', fn ($quiz) => $quiz->where('created_by', (int) $user->id));
                })
                ->orderBy('id')
                ->get();
        }

        return view('admin.links.generate', [
            'activeQuizzes' => $activeQuizzes,
            'generatedLinks' => $generatedLinks,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');

        $data = $request->validate([
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'count' => ['required', 'integer', 'min:1'],
            'usage_type' => ['required', 'in:single,multi'],
            'expires_in_hours' => ['exclude_if:usage_type,single', 'required_if:usage_type,multi', 'integer', 'min:1'],
        ], [
            'quiz_id.required' => 'Pilih Quiz wajib diisi.',
            'count.required' => 'Jumlah Link wajib diisi.',
            'count.min' => 'Jumlah Link harus angka positif.',
            'usage_type.required' => 'Tipe Link wajib dipilih.',
            'expires_in_hours.required_if' => 'Expired (jam) wajib diisi untuk link multi-use.',
            'expires_in_hours.min' => 'Expired (jam) harus angka positif.',
        ]);

        $quiz = Quiz::query()->findOrFail((int) $data['quiz_id']);
        if (! $isSuperAdmin && (int) $quiz->created_by !== (int) ($user?->id ?? 0)) {
            abort(404);
        }
        if (! $quiz->is_active) {
            return back()
                ->withInput()
                ->withErrors(['quiz_id' => 'Quiz tidak aktif.']);
        }

        $count = (int) $data['count'];
        $usageType = (string) $data['usage_type'];
        $expiresInHours = isset($data['expires_in_hours']) ? (int) $data['expires_in_hours'] : null;

        $generatedIds = [];

        DB::transaction(function () use ($quiz, $count, $usageType, $expiresInHours, &$generatedIds): void {
            $userId = (int) auth()->id();

            for ($i = 0; $i < $count; $i++) {
                $generatedIds[] = $this->createUniqueLink($quiz->id, $userId, $usageType, $expiresInHours)->id;
            }
        });

        $request->session()->flash('success', 'Link quiz berhasil dibuat.');
        $request->session()->put('generated_link_ids', $generatedIds);

        return redirect()->to('/admin/generate-link');
    }

    private function createUniqueLink(int $quizId, int $userId, string $usageType, ?int $expiresInHours): QuizLink
    {
        $expiresAt = null;
        if ($usageType === 'multi' && is_int($expiresInHours) && $expiresInHours > 0) {
            $expiresAt = now()->addHours($expiresInHours);
        }

        for ($attempt = 0; $attempt < 10; $attempt++) {
            try {
                return QuizLink::create([
                    'quiz_id' => $quizId,
                    'token' => Str::random(40),
                    'usage_type' => $usageType,
                    'status' => 'unused',
                    'opened_at' => null,
                    'started_at' => null,
                    'submitted_at' => null,
                    'expired_at' => null,
                    'expires_at' => $expiresAt,
                    'created_by' => $userId,
                ]);
            } catch (QueryException $e) {
                $sqlState = $e->errorInfo[0] ?? null;
                $isUniqueViolation = $sqlState === '23505';
                if (! $isUniqueViolation) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Gagal membuat token unik.');
    }
}
