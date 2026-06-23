<?php

use App\Http\Controllers\Admin\AdminResultController;
use App\Livewire\Participant\QuizWork;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Models\QuizResult;
use App\Models\User;
use Livewire\Livewire;

it('grades and displays a participant attempt from its quiz snapshot after the master quiz changes', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $quiz = Quiz::query()->create([
        'title' => 'Snapshot Quiz',
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

    $question = Question::query()->create([
        'quiz_id' => $quiz->id,
        'question_type' => 'multiple_choice',
        'question_text' => 'Pertanyaan lama',
        'question_image_path' => null,
        'difficulty_level' => 'mudah',
        'order_number' => 1,
        'is_active' => true,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $wrongOption = QuestionOption::query()->create([
        'question_id' => $question->id,
        'option_key' => 'A',
        'option_text' => 'Jawaban salah lama',
        'option_image_path' => null,
        'is_correct' => false,
        'sort_order' => 1,
    ]);

    $correctOption = QuestionOption::query()->create([
        'question_id' => $question->id,
        'option_key' => 'B',
        'option_text' => 'Jawaban benar lama',
        'option_image_path' => null,
        'is_correct' => true,
        'sort_order' => 2,
    ]);

    $link = QuizLink::query()->create([
        'quiz_id' => $quiz->id,
        'token' => 'snapshot-token-'.$quiz->id,
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

    $component = Livewire::test(QuizWork::class, ['token' => $link->token])
        ->assertSet('state', 'work')
        ->assertSet('currentQuestionText', 'Pertanyaan lama');

    $question->update(['question_text' => 'Pertanyaan baru']);
    $wrongOption->update(['is_correct' => true, 'option_text' => 'Jawaban benar baru']);
    $correctOption->update(['is_correct' => false, 'option_text' => 'Jawaban salah baru']);

    $component
        ->set('selectedOptionId', $correctOption->id)
        ->call('answerCurrent');

    $result = QuizResult::query()->where('quiz_id', $quiz->id)->first();

    expect($result)->not->toBeNull();
    expect((int) $result->correct_answers)->toBe(1);
    expect((float) $result->score_percentage)->toBe(100.0);

    $attempt = QuizAttempt::query()->where('quiz_link_id', $link->id)->firstOrFail();
    $controller = app(AdminResultController::class);
    $orderedMethod = new ReflectionMethod($controller, 'orderedQuestionIds');
    $orderedMethod->setAccessible(true);
    $rowsMethod = new ReflectionMethod($controller, 'buildQuestionRows');
    $rowsMethod->setAccessible(true);

    $questionIds = $orderedMethod->invoke($controller, $quiz->fresh(), $attempt);
    $rows = $rowsMethod->invoke($controller, $attempt, $questionIds, false);

    expect($rows[0]['question_text'])->toBe('Pertanyaan lama');
    expect($rows[0]['correct_answer'])->toContain('Jawaban benar lama');
    expect($rows[0]['question_text'])->not->toBe('Pertanyaan baru');
    expect($rows[0]['correct_answer'])->not->toContain('Jawaban benar baru');
});
