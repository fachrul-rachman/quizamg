<?php

use App\Livewire\Admin\QuizBuilder;
use App\Models\Quiz;
use App\Models\QuizLink;
use App\Models\User;
use Livewire\Livewire;

it('stores the division selected for an admin account', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($superAdmin)
        ->post('/admin/users', [
            'name' => 'HR Admin',
            'email' => 'hr.admin@example.com',
            'password' => 'secret123',
            'role' => 'admin',
            'division' => 'hr',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'hr.admin@example.com',
        'division' => 'hr',
    ]);
});

it('stores super admin as business while presenting access to all divisions', function () {
    $actingSuperAdmin = User::factory()->create([
        'role' => 'super_admin',
        'division' => 'business',
    ]);

    $this->actingAs($actingSuperAdmin)
        ->post('/admin/users', [
            'name' => 'Second Super Admin',
            'email' => 'second.superadmin@example.com',
            'password' => 'secret123',
            'role' => 'super_admin',
            'division' => 'hr',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.users.index'));

    $createdSuperAdmin = User::query()
        ->where('email', 'second.superadmin@example.com')
        ->firstOrFail();

    expect($createdSuperAdmin->division)->toBe('business');

    createDivisionQuiz($actingSuperAdmin, 'HR Visible to Super Admin', 'hr');
    createDivisionQuiz($actingSuperAdmin, 'Business Visible to Super Admin', 'business');

    $this->actingAs($createdSuperAdmin)
        ->get('/admin/quizzes')
        ->assertOk()
        ->assertSee('HR Visible to Super Admin')
        ->assertSee('Business Visible to Super Admin');

    $this->actingAs($actingSuperAdmin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('Second Super Admin')
        ->assertSee('Semua Divisi');
});

it('shares quizzes within a division and hides quizzes from another division', function () {
    $hrCreator = User::factory()->create(['division' => 'hr']);
    $hrColleague = User::factory()->create(['division' => 'hr']);
    $businessAdmin = User::factory()->create(['division' => 'business']);

    $hrQuiz = createDivisionQuiz($hrCreator, 'HR Quiz', 'hr');
    $businessQuiz = createDivisionQuiz($businessAdmin, 'Business Quiz', 'business');

    $this->actingAs($hrColleague)
        ->get('/admin/quizzes')
        ->assertOk()
        ->assertSee($hrQuiz->title)
        ->assertDontSee($businessQuiz->title);

    $this->actingAs($hrColleague)
        ->get('/admin/quizzes/'.$hrQuiz->id)
        ->assertOk();

    $this->actingAs($businessAdmin)
        ->get('/admin/quizzes/'.$hrQuiz->id)
        ->assertNotFound();
});

it('prevents another division from opening a quiz link detail', function () {
    $hrAdmin = User::factory()->create(['division' => 'hr']);
    $businessAdmin = User::factory()->create(['division' => 'business']);
    $hrQuiz = createDivisionQuiz($hrAdmin, 'Confidential HR Quiz', 'hr');

    $link = QuizLink::query()->create([
        'quiz_id' => $hrQuiz->id,
        'token' => 'division-access-token',
        'usage_type' => 'single',
        'status' => 'unused',
        'created_by' => $hrAdmin->id,
    ]);

    $this->actingAs($businessAdmin)
        ->get('/admin/links/'.$link->id)
        ->assertNotFound();
});

it('assigns a newly created quiz to the admin division', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'division' => 'hr',
    ]);

    $this->actingAs($admin);

    Livewire::test(QuizBuilder::class)
        ->set('title', 'HR Recruitment Quiz')
        ->set('durationMinutes', 30)
        ->set('division', 'business')
        ->set('questions', [validDivisionQuestion()])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/admin/quizzes');

    $this->assertDatabaseHas('quizzes', [
        'title' => 'HR Recruitment Quiz',
        'division' => 'hr',
    ]);
});

function createDivisionQuiz(User $creator, string $title, string $division): Quiz
{
    return Quiz::query()->create([
        'title' => $title,
        'duration_minutes' => 30,
        'shuffle_questions' => false,
        'shuffle_options' => false,
        'instant_feedback_enabled' => false,
        'difficulty_levels_enabled' => false,
        'show_result_to_participant' => false,
        'is_active' => true,
        'division' => $division,
        'created_by' => $creator->id,
        'updated_by' => $creator->id,
    ]);
}

/**
 * @return array<string, mixed>
 */
function validDivisionQuestion(): array
{
    return [
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
    ];
}
