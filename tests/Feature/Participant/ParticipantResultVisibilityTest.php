<?php

use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Models\QuizResult;
use App\Models\User;

it('keeps participant result hidden when quiz option is disabled', function () {
    [$quiz, $link] = createCompletedParticipantResultQuiz(false);

    $this->get('/quiz/'.$link->token.'/done')
        ->assertOk()
        ->assertSee('Jawaban Berhasil Dikirim')
        ->assertDontSee('Total Score')
        ->assertDontSee('Grade A')
        ->assertDontSee('1 / 2');

    expect((bool) $quiz->show_result_to_participant)->toBeFalse();
});

it('shows participant score summary when quiz option is enabled', function () {
    [$quiz, $link] = createCompletedParticipantResultQuiz(true);

    $this->get('/quiz/'.$link->token.'/done')
        ->assertOk()
        ->assertSee('Hasil Quiz')
        ->assertSee('Total Score')
        ->assertSee('50.00%')
        ->assertSee('1 / 2')
        ->assertSee('Total Benar')
        ->assertSee('Total Salah')
        ->assertSee('Grade C')
        ->assertSee('Cukup');

    expect((bool) $quiz->show_result_to_participant)->toBeTrue();
});

/**
 * @return array{0:Quiz,1:QuizLink}
 */
function createCompletedParticipantResultQuiz(bool $showResultToParticipant): array
{
    $admin = User::factory()->create(['role' => 'admin']);

    $quiz = Quiz::query()->create([
        'title' => 'Quiz Result Visibility',
        'description' => null,
        'duration_minutes' => 60,
        'shuffle_questions' => false,
        'shuffle_options' => false,
        'instant_feedback_enabled' => false,
        'difficulty_levels_enabled' => false,
        'show_result_to_participant' => $showResultToParticipant,
        'is_active' => true,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $questionOne = Question::query()->create([
        'quiz_id' => $quiz->id,
        'question_type' => 'multiple_choice',
        'question_text' => 'Question one',
        'question_image_path' => null,
        'difficulty_level' => 'mudah',
        'order_number' => 1,
        'is_active' => true,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $questionTwo = Question::query()->create([
        'quiz_id' => $quiz->id,
        'question_type' => 'multiple_choice',
        'question_text' => 'Question two',
        'question_image_path' => null,
        'difficulty_level' => 'mudah',
        'order_number' => 2,
        'is_active' => true,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $correctOne = QuestionOption::query()->create([
        'question_id' => $questionOne->id,
        'option_key' => 'A',
        'option_text' => 'Correct',
        'is_correct' => true,
        'sort_order' => 1,
    ]);

    $wrongTwo = QuestionOption::query()->create([
        'question_id' => $questionTwo->id,
        'option_key' => 'A',
        'option_text' => 'Wrong',
        'is_correct' => false,
        'sort_order' => 1,
    ]);

    $link = QuizLink::query()->create([
        'quiz_id' => $quiz->id,
        'token' => 'participant-result-token-'.(int) $showResultToParticipant.'-'.$quiz->id,
        'usage_type' => 'single',
        'status' => 'submitted',
        'opened_at' => now(),
        'started_at' => now(),
        'submitted_at' => now(),
        'expired_at' => now(),
        'expires_at' => null,
        'created_by' => $admin->id,
    ]);

    $attempt = QuizAttempt::query()->create([
        'quiz_link_id' => $link->id,
        'quiz_id' => $quiz->id,
        'participant_name' => 'Budi',
        'participant_applied_for' => 'HRD',
        'started_at' => now()->subMinutes(5),
        'submitted_at' => now(),
        'time_limit_minutes' => 60,
        'status' => 'submitted',
    ]);

    AttemptAnswer::query()->create([
        'quiz_attempt_id' => $attempt->id,
        'question_id' => $questionOne->id,
        'selected_option_id' => $correctOne->id,
        'is_correct' => true,
        'answered_at' => now(),
    ]);

    AttemptAnswer::query()->create([
        'quiz_attempt_id' => $attempt->id,
        'question_id' => $questionTwo->id,
        'selected_option_id' => $wrongTwo->id,
        'is_correct' => false,
        'answered_at' => now(),
    ]);

    QuizResult::query()->create([
        'quiz_attempt_id' => $attempt->id,
        'quiz_id' => $quiz->id,
        'total_questions' => 2,
        'correct_answers' => 1,
        'wrong_answers' => 1,
        'unanswered_answers' => 0,
        'score_percentage' => 50,
        'grade_letter' => 'C',
        'grade_label' => 'Cukup',
        'result_status' => 'submitted',
        'calculated_at' => now(),
    ]);

    return [$quiz, $link];
}
