<?php

use App\Livewire\Admin\QuizBuilder;
use App\Models\Quiz;
use App\Models\User;
use Livewire\Livewire;

it('allows admin to save show result to participant option', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    Livewire::test(QuizBuilder::class)
        ->set('title', 'Quiz With Participant Result')
        ->set('durationMinutes', 30)
        ->set('showResultToParticipant', true)
        ->set('questions', [
            [
                'id' => null,
                'question_text' => 'Question one',
                'question_image_path' => null,
                'question_image_upload' => null,
                'remove_question_image' => false,
                'difficulty_level' => 'mudah',
                'question_type' => 'multiple_choice',
                'is_active' => true,
                'options' => [
                    [
                        'id' => null,
                        'option_text' => 'Correct',
                        'option_image_path' => null,
                        'option_image_upload' => null,
                        'remove_option_image' => false,
                        'is_correct' => true,
                    ],
                    [
                        'id' => null,
                        'option_text' => 'Wrong',
                        'option_image_path' => null,
                        'option_image_upload' => null,
                        'remove_option_image' => false,
                        'is_correct' => false,
                    ],
                ],
                'short_answers' => '',
            ],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/admin/quizzes');

    $quiz = Quiz::query()->where('title', 'Quiz With Participant Result')->first();

    expect($quiz)->not->toBeNull();
    expect((bool) $quiz->show_result_to_participant)->toBeTrue();
});
