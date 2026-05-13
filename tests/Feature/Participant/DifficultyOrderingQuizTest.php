<?php

use App\Livewire\Participant\QuizWork;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Models\ShortAnswerKey;
use App\Models\User;
use Livewire\Livewire;

it('keeps difficulty buckets ordered while preserving manual order inside each bucket when shuffle is off', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $quiz = Quiz::query()->create([
        'title' => 'Tes Bertingkat',
        'description' => null,
        'duration_minutes' => 60,
        'shuffle_questions' => false,
        'shuffle_options' => false,
        'instant_feedback_enabled' => false,
        'difficulty_levels_enabled' => true,
        'is_active' => true,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $sulit = createDifficultyQuestion($quiz, $admin, 1, 'sulit');
    $mudahA = createDifficultyQuestion($quiz, $admin, 2, 'mudah');
    $sedang = createDifficultyQuestion($quiz, $admin, 3, 'sedang');
    $mudahB = createDifficultyQuestion($quiz, $admin, 4, 'mudah');

    $link = createDifficultyLinkAndAttempt($quiz, $admin);

    Livewire::test(QuizWork::class, ['token' => $link->token])
        ->assertSet('state', 'work')
        ->assertSet('questionIds', [$mudahA->id, $mudahB->id, $sedang->id, $sulit->id])
        ->assertSet('currentDifficultyLevel', 'mudah');
});

it('keeps shuffled questions inside their difficulty buckets', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $quiz = Quiz::query()->create([
        'title' => 'Tes Bertingkat Shuffle',
        'description' => null,
        'duration_minutes' => 60,
        'shuffle_questions' => true,
        'shuffle_options' => false,
        'instant_feedback_enabled' => false,
        'difficulty_levels_enabled' => true,
        'is_active' => true,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    createDifficultyQuestion($quiz, $admin, 1, 'sulit');
    createDifficultyQuestion($quiz, $admin, 2, 'mudah');
    createDifficultyQuestion($quiz, $admin, 3, 'sangat_sulit');
    createDifficultyQuestion($quiz, $admin, 4, 'sedang');
    createDifficultyQuestion($quiz, $admin, 5, 'mudah');

    $link = createDifficultyLinkAndAttempt($quiz, $admin);

    $component = Livewire::test(QuizWork::class, ['token' => $link->token])
        ->assertSet('state', 'work');

    $orderedIds = $component->get('questionIds');
    $levels = Question::query()
        ->whereIn('id', $orderedIds)
        ->get(['id', 'difficulty_level'])
        ->keyBy('id');

    $orderedLevels = array_map(
        fn (int $id) => (string) $levels->get($id)->difficulty_level,
        $orderedIds
    );

    expect($orderedLevels)->toBe(['mudah', 'mudah', 'sedang', 'sulit', 'sangat_sulit']);
});

function createDifficultyQuestion(Quiz $quiz, User $admin, int $order, string $difficulty): Question
{
    $question = Question::query()->create([
        'quiz_id' => $quiz->id,
        'question_type' => 'short_answer',
        'question_text' => 'Soal '.$order,
        'question_image_path' => null,
        'difficulty_level' => $difficulty,
        'order_number' => $order,
        'is_active' => true,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    ShortAnswerKey::query()->create([
        'question_id' => $question->id,
        'answer_text' => 'ok',
        'normalized_answer_text' => 'ok',
        'sort_order' => 1,
    ]);

    return $question;
}

function createDifficultyLinkAndAttempt(Quiz $quiz, User $admin): QuizLink
{
    $link = QuizLink::query()->create([
        'quiz_id' => $quiz->id,
        'token' => 'difficulty-token-'.$quiz->id,
        'usage_type' => 'single',
        'status' => 'in_progress',
        'opened_at' => now(),
        'started_at' => now(),
        'submitted_at' => null,
        'expired_at' => null,
        'expires_at' => null,
        'created_by' => $admin->id,
    ]);

    QuizAttempt::query()->create([
        'quiz_link_id' => $link->id,
        'quiz_id' => $quiz->id,
        'participant_name' => 'Budi',
        'participant_applied_for' => 'HRD',
        'started_at' => now(),
        'submitted_at' => null,
        'time_limit_minutes' => 60,
        'status' => 'in_progress',
    ]);

    return $link;
}
