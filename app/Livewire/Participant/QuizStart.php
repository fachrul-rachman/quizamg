<?php

namespace App\Livewire\Participant;

use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Support\ParticipantAppliedForNormalizer;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class QuizStart extends Component
{
    public string $token;

    private const SESSION_ATTEMPT_KEY_PREFIX = 'quiz_attempt_id_for_token_';

    public string $state = 'loading';
    public string $title = '';
    public int $durationMinutes = 0;
    public bool $instantFeedbackEnabled = false;
    public string $finalMessage = '';

    public string $participantName = '';
    public string $participantAppliedFor = '';

    public function mount(string $token): void
    {
        $this->token = $token;

        $link = QuizLink::query()
            ->with(['quiz:id,title,duration_minutes,is_active,instant_feedback_enabled', 'attempt'])
            ->where('token', $token)
            ->first();

        if (! $link) {
            $this->state = 'invalid';
            return;
        }

        if (in_array($link->status, ['submitted', 'expired'], true)) {
            $this->state = 'final';
            $this->finalMessage = $link->status === 'submitted'
                ? 'Quiz ini sudah selesai dikerjakan.'
                : 'Waktu pengerjaan quiz ini sudah habis.';
            return;
        }

        if (! $link->quiz || ! $link->quiz->is_active) {
            $this->state = 'unavailable';
            return;
        }

        if ($link->status === 'unused') {
            $link->status = 'opened';
            $link->opened_at = $link->opened_at ?? now();
            $link->save();
        }

        $this->title = (string) $link->quiz->title;
        $this->durationMinutes = (int) $link->quiz->duration_minutes;
        $this->instantFeedbackEnabled = (bool) $link->quiz->instant_feedback_enabled;

        if ($link->usage_type === 'multi') {
            if ($this->isMultiUseExpired($link)) {
                if ($link->status !== 'expired') {
                    $link->status = 'expired';
                    $link->expired_at = $link->expired_at ?? now();
                    $link->save();
                }

                $this->state = 'final';
                $this->finalMessage = 'Waktu untuk mengerjakan quiz ini sudah habis.';
                return;
            }

            $attempt = $this->getAttemptFromSession($link);
            if ($attempt) {
                $this->participantName = (string) $attempt->participant_name;
                $this->participantAppliedFor = (string) $attempt->participant_applied_for;

                if ($attempt->status === 'in_progress') {
                    $this->redirect('/quiz/'.$token.'/work', navigate: false);
                    return;
                }

                if (in_array($attempt->status, ['submitted', 'auto_submitted'], true)) {
                    $this->clearAttemptSession($link);
                }
            }
        } else {
            if ($link->attempt) {
                $this->participantName = (string) $link->attempt->participant_name;
                $this->participantAppliedFor = (string) $link->attempt->participant_applied_for;

                if ($link->attempt->status === 'in_progress') {
                    $this->redirect('/quiz/'.$token.'/work', navigate: false);
                    return;
                }
            }
        }

        $this->state = 'start';
    }

    public function saveIdentity(): void
    {
        $link = $this->getLinkOrFail();
        $attempt = $link->usage_type === 'multi'
            ? $this->getAttemptFromSession($link)
            : $link->attempt;

        if (! in_array($link->status, ['unused', 'opened'], true)) {
            throw ValidationException::withMessages([
                'participantName' => 'Status link quiz tidak valid.',
            ]);
        }

        if ($attempt && $attempt->status !== 'not_started') {
            throw ValidationException::withMessages([
                'participantName' => 'Tidak bisa mengubah identitas setelah test dimulai.',
            ]);
        }

        $this->validate([
            'participantName' => ['required', 'string', 'max:255'],
            'participantAppliedFor' => ['required', 'string', 'max:255'],
        ], [], [
            'participantName' => 'Nama Peserta',
            'participantAppliedFor' => 'Jabatan',
        ]);

        $this->participantAppliedFor = ParticipantAppliedForNormalizer::normalize($this->participantAppliedFor);

        if ($link->usage_type === 'multi') {
            if (! $attempt) {
                $attempt = QuizAttempt::create([
                    'quiz_link_id' => $link->id,
                    'quiz_id' => $link->quiz_id,
                    'participant_name' => $this->participantName,
                    'participant_applied_for' => $this->participantAppliedFor,
                    'started_at' => null,
                    'submitted_at' => null,
                    'time_limit_minutes' => (int) $link->quiz->duration_minutes,
                    'status' => 'not_started',
                ]);
                $this->setAttemptSession($link, (int) $attempt->id);
            } else {
                $attempt->update([
                    'participant_name' => $this->participantName,
                    'participant_applied_for' => $this->participantAppliedFor,
                ]);
            }
        } else {
            QuizAttempt::updateOrCreate(
                ['quiz_link_id' => $link->id],
                [
                    'quiz_id' => $link->quiz_id,
                    'participant_name' => $this->participantName,
                    'participant_applied_for' => $this->participantAppliedFor,
                    'started_at' => null,
                    'submitted_at' => null,
                    'time_limit_minutes' => (int) $link->quiz->duration_minutes,
                    'status' => 'not_started',
                ],
            );
        }

        session()->flash('success', 'Identitas berhasil disimpan. Test belum dimulai.');
    }

    public function startTest(): void
    {
        $link = $this->getLinkOrFail();

        if (! in_array($link->status, ['unused', 'opened'], true)) {
            return;
        }

        $this->validate([
            'participantName' => ['required', 'string', 'max:255'],
            'participantAppliedFor' => ['required', 'string', 'max:255'],
        ], [], [
            'participantName' => 'Nama Peserta',
            'participantAppliedFor' => 'Jabatan',
        ]);

        $this->participantAppliedFor = ParticipantAppliedForNormalizer::normalize($this->participantAppliedFor);

        if ($link->usage_type === 'multi' && $this->isMultiUseExpired($link)) {
            $this->state = 'final';
            $this->finalMessage = 'Waktu untuk mengerjakan quiz ini sudah habis.';
            return;
        }

        $now = now();

        if ($link->usage_type === 'multi') {
            $attempt = $this->getAttemptFromSession($link);
            if (! $attempt) {
                $attempt = QuizAttempt::create([
                    'quiz_link_id' => $link->id,
                    'quiz_id' => $link->quiz_id,
                    'participant_name' => $this->participantName,
                    'participant_applied_for' => $this->participantAppliedFor,
                    'started_at' => $now,
                    'submitted_at' => null,
                    'time_limit_minutes' => (int) $link->quiz->duration_minutes,
                    'status' => 'in_progress',
                ]);
                $this->setAttemptSession($link, (int) $attempt->id);
            } else {
                if ($attempt->status !== 'not_started') {
                    return;
                }

                $attempt->update([
                    'participant_name' => $this->participantName,
                    'participant_applied_for' => $this->participantAppliedFor,
                    'started_at' => $now,
                    'status' => 'in_progress',
                ]);
            }

            $link->update([
                'status' => $link->status === 'unused' ? 'opened' : $link->status,
                'started_at' => $link->started_at ?? $now,
            ]);
        } else {
            $attempt = QuizAttempt::query()->where('quiz_link_id', $link->id)->first();
            if ($attempt && $attempt->status !== 'not_started') {
                return;
            }

            if (! $attempt) {
                $attempt = QuizAttempt::create([
                    'quiz_link_id' => $link->id,
                    'quiz_id' => $link->quiz_id,
                    'participant_name' => $this->participantName,
                    'participant_applied_for' => $this->participantAppliedFor,
                    'started_at' => $now,
                    'submitted_at' => null,
                    'time_limit_minutes' => (int) $link->quiz->duration_minutes,
                    'status' => 'in_progress',
                ]);
            } else {
                $attempt->update([
                    'participant_name' => $this->participantName,
                    'participant_applied_for' => $this->participantAppliedFor,
                    'started_at' => $now,
                    'status' => 'in_progress',
                ]);
            }

            $link->update([
                'status' => 'in_progress',
                'started_at' => $link->started_at ?? $now,
            ]);
        }

        $this->redirect('/quiz/'.$this->token.'/work', navigate: false);
    }

    private function getLinkOrFail(): QuizLink
    {
        $link = QuizLink::query()
            ->with(['quiz:id,title,duration_minutes,is_active,instant_feedback_enabled', 'attempt'])
            ->where('token', $this->token)
            ->first();

        if (! $link) {
            abort(404);
        }

        if (in_array($link->status, ['submitted', 'expired'], true)) {
            abort(404);
        }

        if (! $link->quiz || ! $link->quiz->is_active) {
            abort(404);
        }

        if ($link->usage_type === 'multi' && $this->isMultiUseExpired($link)) {
            if ($link->status !== 'expired') {
                $link->status = 'expired';
                $link->expired_at = $link->expired_at ?? now();
                $link->save();
            }

            abort(404);
        }

        return $link;
    }

    private function isMultiUseExpired(QuizLink $link): bool
    {
        if (! $link->expires_at) {
            return false;
        }

        return CarbonImmutable::now()->gte(CarbonImmutable::parse($link->expires_at));
    }

    private function getAttemptSessionKey(QuizLink $link): string
    {
        return self::SESSION_ATTEMPT_KEY_PREFIX.$link->token;
    }

    private function setAttemptSession(QuizLink $link, int $attemptId): void
    {
        session()->put($this->getAttemptSessionKey($link), $attemptId);
    }

    private function clearAttemptSession(QuizLink $link): void
    {
        session()->forget($this->getAttemptSessionKey($link));
    }

    private function getAttemptFromSession(QuizLink $link): ?QuizAttempt
    {
        $attemptId = session()->get($this->getAttemptSessionKey($link));
        if (! is_int($attemptId) || $attemptId <= 0) {
            return null;
        }

        return QuizAttempt::query()
            ->where('id', $attemptId)
            ->where('quiz_link_id', $link->id)
            ->first();
    }

    private function isStillWithinTimeLimit(QuizAttempt $attempt): bool
    {
        if (! $attempt->started_at) {
            return true;
        }

        $startedAt = CarbonImmutable::parse($attempt->started_at);
        $deadline = $startedAt->addMinutes((int) $attempt->time_limit_minutes);
        return CarbonImmutable::now()->lt($deadline);
    }

    public function render()
    {
        return view('livewire.participant.quiz-start');
    }
}
