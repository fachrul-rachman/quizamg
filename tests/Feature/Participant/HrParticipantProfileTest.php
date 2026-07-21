<?php

use App\Livewire\Participant\QuizStart;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Models\QuizResult;
use App\Models\User;
use App\Services\Discord\DiscordResultWebhookService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('shows and requires additional participant fields only for hr quizzes', function () {
    [$hrQuiz, $hrLink] = createProfileQuizLink('hr', 'hr-profile-token');
    [, $businessLink] = createProfileQuizLink('business', 'business-profile-token');

    Livewire::test(QuizStart::class, ['token' => $hrLink->token])
        ->assertDontSee('Jabatan')
        ->assertSee('Usia')
        ->assertSee('Tinggi Badan')
        ->assertSee('Berat Badan')
        ->assertSee('Pekerjaan Terakhir')
        ->assertSee('Perusahaan Terakhir')
        ->assertSee('Sejak Kapan Bekerja')
        ->assertSee('Domisili')
        ->set('participantName', 'Budi')
        ->call('saveIdentity')
        ->assertHasErrors([
            'participantAge',
            'participantHeightCm',
            'participantWeightKg',
            'participantLastJob',
            'participantLastCompany',
            'participantLastJobStartedMonth',
            'participantCurrentDomicile',
        ]);

    Livewire::test(QuizStart::class, ['token' => $businessLink->token])
        ->assertDontSee('Usia')
        ->assertDontSee('Tinggi Badan')
        ->assertDontSee('Pekerjaan Terakhir')
        ->assertDontSee('Sejak Kapan Bekerja')
        ->set('participantName', 'Siti')
        ->set('participantAppliedFor', 'Sales')
        ->call('saveIdentity')
        ->assertHasNoErrors();

    expect($hrQuiz->division)->toBe('hr');
});

it('stores hr participant profile and discards those fields for business quizzes', function () {
    [, $hrLink] = createProfileQuizLink('hr', 'hr-profile-save-token');

    Livewire::test(QuizStart::class, ['token' => $hrLink->token])
        ->set('participantName', 'Budi')
        ->set('participantAge', '28')
        ->set('participantHeightCm', '172.5')
        ->set('participantWeightKg', '68.5')
        ->set('participantLastJob', 'HR Generalist')
        ->set('participantLastCompany', 'PT Lama')
        ->set('participantLastJobStartedMonth', '2023-01')
        ->set('participantCurrentDomicile', 'Jakarta Selatan')
        ->call('saveIdentity')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('quiz_attempts', [
        'quiz_link_id' => $hrLink->id,
        'participant_applied_for' => '',
        'participant_age' => 28,
        'participant_height_cm' => 172.5,
        'participant_weight_kg' => 68.5,
        'participant_last_job' => 'HR Generalist',
        'participant_last_company' => 'PT Lama',
        'participant_current_domicile' => 'Jakarta Selatan',
    ]);

    $hrAttempt = QuizAttempt::query()->where('quiz_link_id', $hrLink->id)->firstOrFail();
    expect($hrAttempt->participant_last_job_started_at?->format('Y-m-d'))->toBe('2023-01-01');

    [, $businessLink] = createProfileQuizLink('business', 'business-profile-save-token');

    Livewire::test(QuizStart::class, ['token' => $businessLink->token])
        ->set('participantName', 'Siti')
        ->set('participantAppliedFor', 'Sales')
        ->set('participantAge', '30')
        ->set('participantHeightCm', '165')
        ->set('participantWeightKg', '55')
        ->set('participantLastJob', 'HR Generalist')
        ->set('participantLastCompany', 'PT Lama')
        ->set('participantLastJobStartedMonth', '2023-01')
        ->set('participantCurrentDomicile', 'Jakarta')
        ->call('saveIdentity')
        ->assertHasNoErrors();

    $businessAttempt = QuizAttempt::query()
        ->where('quiz_link_id', $businessLink->id)
        ->firstOrFail();

    expect($businessAttempt->participant_age)->toBeNull()
        ->and($businessAttempt->participant_last_job)->toBeNull()
        ->and($businessAttempt->participant_last_job_started_at)->toBeNull()
        ->and($businessAttempt->participant_current_domicile)->toBeNull();
});

it('includes hr participant profile in discord and pdf output', function () {
    [$quiz, $link, $admin] = createProfileQuizLink('hr', 'hr-output-token');
    $admin->update(['discord_webhook_url' => 'https://discord.test/webhook']);

    $attempt = QuizAttempt::query()->create([
        'quiz_link_id' => $link->id,
        'quiz_id' => $quiz->id,
        'participant_name' => 'Budi',
        'participant_applied_for' => '',
        'participant_age' => 28,
        'participant_height_cm' => 172.5,
        'participant_weight_kg' => 68.5,
        'participant_last_job' => 'HR Generalist',
        'participant_last_company' => 'PT Lama',
        'participant_last_job_started_at' => '2023-01-01',
        'participant_current_domicile' => 'Jakarta Selatan',
        'started_at' => now()->subMinutes(10),
        'submitted_at' => now(),
        'time_limit_minutes' => 30,
        'status' => 'submitted',
    ]);

    $result = QuizResult::query()->create([
        'quiz_attempt_id' => $attempt->id,
        'quiz_id' => $quiz->id,
        'total_questions' => 10,
        'correct_answers' => 8,
        'wrong_answers' => 2,
        'unanswered_answers' => 0,
        'score_percentage' => 80,
        'grade_letter' => 'B',
        'grade_label' => 'Baik',
        'result_status' => 'submitted',
        'calculated_at' => now(),
    ]);

    Http::fake(['https://discord.test/*' => Http::response('', 204)]);

    app(DiscordResultWebhookService::class)->sendForResultId($result->id);

    Http::assertSent(function ($request): bool {
        $fields = collect($request->data()['embeds'][0]['fields'] ?? [])
            ->mapWithKeys(fn (array $field) => [$field['name'] => $field['value']]);

        return $fields->get('Usia') === '28 tahun'
            && $fields->get('Tinggi / Berat') === '172.50 cm / 68.50 kg'
            && $fields->get('Pekerjaan Terakhir') === 'HR Generalist'
            && $fields->get('Perusahaan Terakhir') === 'PT Lama'
            && $fields->get('Sejak Kapan Bekerja') === 'Januari 2023'
            && ! $fields->has('Jabatan')
            && $fields->get('Domisili') === 'Jakarta Selatan';
    });

    $html = view('pdf.result', [
        'quiz' => $quiz,
        'attempt' => $attempt,
        'result' => $result,
        'rows' => [],
        'printedAt' => now(),
    ])->render();

    expect($html)->toContain('Usia')
        ->and($html)->toContain('28 tahun')
        ->and($html)->toContain('172.50 cm')
        ->and($html)->toContain('68.50 kg')
        ->and($html)->toContain('HR Generalist')
        ->and($html)->toContain('PT Lama')
        ->and($html)->toContain('Januari 2023')
        ->and($html)->toContain('Jakarta Selatan');
});

/**
 * @return array{0:Quiz,1:QuizLink,2:User}
 */
function createProfileQuizLink(string $division, string $token): array
{
    $admin = User::factory()->create([
        'role' => 'admin',
        'division' => $division,
    ]);

    $quiz = Quiz::query()->create([
        'title' => strtoupper($division).' Profile Quiz',
        'duration_minutes' => 30,
        'shuffle_questions' => false,
        'shuffle_options' => false,
        'instant_feedback_enabled' => false,
        'difficulty_levels_enabled' => false,
        'show_result_to_participant' => false,
        'is_active' => true,
        'division' => $division,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $link = QuizLink::query()->create([
        'quiz_id' => $quiz->id,
        'token' => $token,
        'usage_type' => 'single',
        'status' => 'unused',
        'created_by' => $admin->id,
    ]);

    return [$quiz, $link, $admin];
}
