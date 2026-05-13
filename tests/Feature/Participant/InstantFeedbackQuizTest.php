<?php

use App\Livewire\Participant\QuizWork;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Models\User;
use Livewire\Livewire;

it('locks multiple choice answer after first selection when instant feedback is enabled', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $quiz = Quiz::query()->create([
        'title' => 'WPT Latihan',
        'description' => null,
        'category_id' => null,
        'duration_minutes' => 60,
        'shuffle_questions' => false,
        'shuffle_options' => false,
        'instant_feedback_enabled' => true,
        'is_active' => true,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $question = Question::query()->create([
        'quiz_id' => $quiz->id,
        'question_type' => 'multiple_choice',
        'question_text' => '2 + 2 = ?',
        'question_image_path' => null,
        'order_number' => 1,
        'is_active' => true,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $wrongOption = QuestionOption::query()->create([
        'question_id' => $question->id,
        'option_key' => 'A',
        'option_text' => '3',
        'option_image_path' => null,
        'is_correct' => false,
        'sort_order' => 1,
    ]);

    $correctOption = QuestionOption::query()->create([
        'question_id' => $question->id,
        'option_key' => 'B',
        'option_text' => '4',
        'option_image_path' => null,
        'is_correct' => true,
        'sort_order' => 2,
    ]);

    $link = QuizLink::query()->create([
        'quiz_id' => $quiz->id,
        'token' => 'token-instant-feedback-1234567890',
        'status' => 'in_progress',
        'opened_at' => now(),
        'started_at' => now(),
        'submitted_at' => null,
        'expired_at' => null,
        'created_by' => $admin->id,
    ]);

    $attempt = QuizAttempt::query()->create([
        'quiz_link_id' => $link->id,
        'quiz_id' => $quiz->id,
        'participant_name' => 'Budi',
        'participant_applied_for' => 'HRD',
        'started_at' => now(),
        'submitted_at' => null,
        'time_limit_minutes' => 60,
        'status' => 'in_progress',
    ]);

    Livewire::test(QuizWork::class, ['token' => $link->token])
        ->assertSet('state', 'work')
        ->set('selectedOptionId', $wrongOption->id)
        ->assertSet('currentAnswerLocked', true)
        ->assertSet('currentAnswerIsCorrect', false)
        ->assertSet('selectedOptionId', $wrongOption->id)
        ->set('selectedOptionId', $correctOption->id)
        ->assertSet('selectedOptionId', $wrongOption->id)
        ->assertSet('currentAnswerLocked', true);

    $answer = AttemptAnswer::query()
        ->where('quiz_attempt_id', $attempt->id)
        ->where('question_id', $question->id)
        ->first();

    expect($answer)->not->toBeNull();
    expect($answer->selected_option_id)->toBe($wrongOption->id);
    expect((bool) $answer->is_correct)->toBeFalse();
});
