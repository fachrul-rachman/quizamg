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
        $search = trim((string) $request->query('search', ''));
        $quizId = (string) $request->query('quiz_id', '');
        $status = (string) $request->query('status', 'all');

        $query = QuizLink::query()
            ->with('quiz:id,title')
            ->withCount('attempts')
            ->when($user && ! $user->isSuperAdmin(), function ($q) use ($user) {
                $q->whereHas('quiz', fn ($quiz) => $quiz->where('division', $user->division));
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
            ->when($user, fn ($q) => $q->visibleTo($user))
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
        $quizLink->loadMissing('quiz:id,division');
        if (! $user || ! $quizLink->quiz || ! $quizLink->quiz->isAccessibleBy($user)) {
            abort(404);
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
