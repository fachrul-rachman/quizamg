<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizLink;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminQuizLinkController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');

        $search = trim((string) $request->query('search', ''));
        $quizId = (string) $request->query('quiz_id', '');
        $status = (string) $request->query('status', 'all');

        $query = QuizLink::query()
            ->with('quiz:id,title')
            ->withCount('attempts')
            ->when(! $isSuperAdmin && $user, function ($q) use ($user) {
                $q->whereHas('quiz', fn ($quiz) => $quiz->where('created_by', (int) $user->id));
            })
            ->orderByDesc('id');

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $query->whereRaw('LOWER(token) LIKE ?', ['%'.$needle.'%']);
        }

        if ($quizId !== '') {
            $query->where('quiz_id', (int) $quizId);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $links = $query->paginate(10)->withQueryString();

        $quizzes = Quiz::query()
            ->when(! $isSuperAdmin && $user, fn ($q) => $q->where('created_by', (int) $user->id))
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('admin.links.index', [
            'links' => $links,
            'quizzes' => $quizzes,
            'search' => $search,
            'quizId' => $quizId,
            'status' => $status,
        ]);
    }

    public function show(QuizLink $quizLink): View
    {
        $user = request()->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');
        if (! $isSuperAdmin) {
            $quizLink->loadMissing('quiz:id,created_by');
            if ((int) ($quizLink->quiz?->created_by ?? 0) !== (int) ($user?->id ?? 0)) {
                abort(404);
            }
        }

        $quizLink->load([
            'quiz:id,title',
            'creator:id,name',
            'attempt',
            'attempts' => function ($query) {
                $query
                    ->select([
                        'id',
                        'quiz_link_id',
                        'participant_name',
                        'participant_applied_for',
                        'started_at',
                        'submitted_at',
                        'status',
                    ])
                    ->with([
                        'result:id,quiz_attempt_id,score_percentage,grade_letter,grade_label',
                    ])
                    ->orderByDesc('id');
            },
        ]);

        return view('admin.links.show', [
            'link' => $quizLink,
        ]);
    }
}
