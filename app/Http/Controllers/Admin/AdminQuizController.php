<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminQuizController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $query = Quiz::query()
            ->with(['creator:id,name'])
            ->addSelect([
                'active_questions_count' => DB::table('questions')
                    ->selectRaw('count(*)')
                    ->whereColumn('questions.quiz_id', 'quizzes.id')
                    ->whereNull('questions.deleted_at')
                    ->where('questions.is_active', true),
            ]);

        if (! $isSuperAdmin && $user) {
            $query->where('created_by', (int) $user->id);
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $query->whereRaw('LOWER(title) LIKE ?', ['%'.$needle.'%']);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $quizzes = $query
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.quizzes.index', [
            'quizzes' => $quizzes,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.quizzes.create');
    }

    public function show(Quiz $quiz): View
    {
        $user = request()->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');
        if (! $isSuperAdmin && (int) $quiz->created_by !== (int) ($user?->id ?? 0)) {
            abort(404);
        }

        $quiz->load([
            'creator:id,name',
            'updater:id,name',
            'questions' => fn ($q) => $q->orderBy('order_number'),
            'questions.options' => fn ($q) => $q->orderBy('sort_order'),
            'questions.shortAnswerKeys' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return view('admin.quizzes.show', [
            'quiz' => $quiz,
        ]);
    }

    public function edit(Quiz $quiz): View
    {
        $user = request()->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');
        if (! $isSuperAdmin && (int) $quiz->created_by !== (int) ($user?->id ?? 0)) {
            abort(404);
        }

        return view('admin.quizzes.edit', [
            'quiz' => $quiz,
        ]);
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $user = request()->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');
        if (! $isSuperAdmin && (int) $quiz->created_by !== (int) ($user?->id ?? 0)) {
            abort(404);
        }

        $quiz->delete();

        return redirect()
            ->to('/admin/quizzes')
            ->with('success', 'Quiz berhasil dihapus.');
    }
}
