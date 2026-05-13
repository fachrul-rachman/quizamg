<?php

use App\Livewire\Participant\QuizWork;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Models\ShortAnswerKey;
use App\Models\User;
use Livewire\Livewire;

it('shows skipped question buttons and removes them after the question is answered', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    [$quiz, $questions] = createSkippedNavigationQuiz($admin, 3);
    $link = createSkippedNavigationLinkAndAttempt($quiz, $admin);

    Livewire::test(QuizWork::class, ['token' => $link->token])
        ->assertSet('state', 'work')
        ->assertSet('step', 1)
        ->call('skipCurrent')
        ->assertSet('step', 2)
        ->assertSet('skippedQuestionButtons', [
            ['question_id' => $questions[0]->id, 'step' => 1],
        ])
        ->call('goToSkippedQuestion', $questions[0]->id)
        ->assertSet('step', 1)
        ->set('shortAnswerText', 'ok')
        ->call('answerCurrent')
        ->assertSet('skippedQuestionButtons', []);

    $answer = AttemptAnswer::query()
        ->where('quiz_attempt_id', QuizAttempt::query()->where('quiz_link_id', $link->id)->value('id'))
        ->where('question_id', $questions[0]->id)
        ->first();

    expect($answer)->not->toBeNull();
    expect($answer->skipped_at)->toBeNull();
    expect($answer->answer_text)->toBe('ok');
});

it('does not auto-submit while skipped questions remain', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    [$quiz, $questions] = createSkippedNavigationQuiz($admin, 3);
    $link = createSkippedNavigationLinkAndAttempt($quiz, $admin);

    Livewire::test(QuizWork::class, ['token' => $link->token])
        ->assertSet('state', 'work')
        ->call('skipCurrent')
        ->assertSet('step', 2)
        ->set('shortAnswerText', 'ok')
        ->call('answerCurrent')
        ->assertSet('step', 3)
        ->set('shortAnswerText', 'ok')
        ->call('answerCurrent')
        ->assertSet('state', 'work')
        ->assertSet('step', 1)
        ->assertSet('currentQuestionId', $questions[0]->id)
        ->assertSet('skippedQuestionButtons', [
            ['question_id' => $questions[0]->id, 'step' => 1],
        ]);

    $attempt = QuizAttempt::query()->where('quiz_link_id', $link->id)->first();

    expect($attempt)->not->toBeNull();
    expect($attempt->status)->toBe('in_progress');
});

/**
 * @return array{0:Quiz,1:array<int, Question>}
 */
function createSkippedNavigationQuiz(User $admin, int $questionCount): array
{
    $quiz = Quiz::query()->create([
        'title' => 'Quiz Skip Navigation',
        'description' => null,
        'duration_minutes' => 60,
        'shuffle_questions' => false,
        'shuffle_options' => false,
        'instant_feedback_enabled' => false,
        'difficulty_levels_enabled' => false,
        'is_active' => true,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $questions = [];
    for ($i = 1; $i <= $questionCount; $i++) {
        $question = Question::query()->create([
            'quiz_id' => $quiz->id,
            'question_type' => 'short_answer',
            'question_text' => 'Soal skip '.$i,
            'question_image_path' => null,
            'difficulty_level' => 'mudah',
            'order_number' => $i,
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

        $questions[] = $question;
    }

    return [$quiz, $questions];
}

function createSkippedNavigationLinkAndAttempt(Quiz $quiz, User $admin): QuizLink
{
    $link = QuizLink::query()->create([
        'quiz_id' => $quiz->id,
        'token' => 'skip-navigation-token-'.$quiz->id,
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
