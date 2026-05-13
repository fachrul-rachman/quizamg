<div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-950" @if($state==='work') wire:poll.1s="tick" @endif>
    @if ($state === 'invalid')
        <h1 class="text-xl font-semibold">Link Quiz Tidak Valid</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Link yang Anda buka tidak ditemukan atau sudah tidak berlaku. Periksa kembali link dari admin.</div>
    @elseif ($state === 'submitted')
        <h1 class="text-xl font-semibold">Link Quiz Tidak Bisa Digunakan</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Quiz ini sudah selesai dikerjakan.</div>
    @elseif ($state === 'expired' || $state === 'expired_view')
        <h1 class="text-xl font-semibold">Link Quiz Tidak Bisa Digunakan</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Waktu pengerjaan quiz ini sudah habis.</div>
    @elseif ($state === 'unavailable')
        <h1 class="text-xl font-semibold">Quiz tidak tersedia.</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Quiz sedang nonaktif atau link ini tidak dapat dipakai lagi.</div>
    @elseif ($state === 'no_questions')
        <h1 class="text-xl font-semibold">Soal belum tersedia.</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Data soal tidak ditemukan. Hubungi admin untuk memeriksa quiz ini.</div>
    @elseif ($state === 'work')
        <div class="sticky top-0 z-10 -mx-6 -mt-6 mb-5 border-b border-zinc-200 bg-white/95 px-6 py-4 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/80">
            <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Nama Quiz</div>
                <div class="mt-1 text-lg font-semibold">{{ $title }}</div>
                <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Nama: {{ $participantName }}</div>
                <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Jabatan: {{ $participantAppliedFor }}</div>
            </div>

            @php
                $isAmber = $secondsRemaining > 0 && $secondsRemaining <= 300;
                $isRed = $secondsRemaining > 0 && $secondsRemaining <= 60;
                $timerWrap = $isRed
                    ? 'border-rose-200 bg-rose-50 text-rose-900'
                    : ($isAmber
                        ? 'border-amber-200 bg-amber-50 text-amber-900'
                        : 'border-zinc-200 bg-zinc-50 text-zinc-900');
            @endphp
            <div class="rounded-md border px-3 py-2 text-sm font-semibold {{ $timerWrap }}">
                Sisa Waktu:
                {{ $secondsRemaining >= 3600 ? gmdate('H:i:s', $secondsRemaining) : gmdate('i:s', $secondsRemaining) }}
            </div>
            </div>
        </div>

        @if ($instantFeedbackEnabled)
            <div class="mt-4 rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-100">
                Mode jawaban instan aktif: setelah klik "Jawab", jawaban terkunci dan Anda otomatis lanjut ke soal berikutnya.
            </div>
        @else
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100">
                Gunakan "Skip" untuk menandai soal yang ingin dikerjakan lagi sebelum waktu habis.
            </div>
        @endif

        <div class="mt-6">
            @if (!empty($skippedQuestionButtons))
                <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900/50 dark:bg-amber-950/20">
                    <div class="text-sm font-semibold text-amber-950 dark:text-amber-100">Soal di-skip</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($skippedQuestionButtons as $item)
                            @php
                                $isCurrentSkipped = (int) $currentQuestionId === (int) $item['question_id'];
                                $buttonClass = $isCurrentSkipped
                                    ? 'border-amber-700 bg-amber-700 text-white'
                                    : 'border-amber-300 bg-white text-amber-900 hover:bg-amber-100 dark:border-amber-700 dark:bg-zinc-950 dark:text-amber-100 dark:hover:bg-amber-950/40';
                            @endphp
                            <button
                                type="button"
                                wire:click="goToSkippedQuestion({{ (int) $item['question_id'] }})"
                                @disabled($pendingAutoAdvance)
                                class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm font-semibold shadow-sm transition disabled:opacity-50 {{ $buttonClass }}"
                            >
                                {{ $item['step'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="text-sm font-semibold">Soal {{ $step }} dari {{ count($questionIds) }}</div>
                    @if ($difficultyLevelsEnabled && $currentDifficultyLevel)
                        @php
                            $difficultyClass = match ($currentDifficultyLevel) {
                                'mudah' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                'sedang' => 'border-sky-200 bg-sky-50 text-sky-800',
                                'sulit' => 'border-amber-200 bg-amber-50 text-amber-800',
                                'sangat_sulit' => 'border-rose-200 bg-rose-50 text-rose-800',
                                default => 'border-slate-200 bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $difficultyClass }}">
                            {{ \App\Support\QuestionDifficulty::label($currentDifficultyLevel) }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="mt-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-sm text-zinc-600 dark:text-zinc-300 mb-2">Soal</div>
                @if (filled($currentQuestionText))
                    <div class="whitespace-pre-line">{{ $currentQuestionText }}</div>
                @endif

                @if ($currentQuestionImagePath)
                    <div class="@if(filled($currentQuestionText)) mt-4 @endif">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($currentQuestionImagePath) }}" alt="Gambar soal" class="max-h-72 rounded-md border border-zinc-200 object-contain dark:border-zinc-800" />
                    </div>
                @endif

                @if ($currentQuestionType === 'multiple_choice')
                    <div class="mt-4 space-y-2">
                        @foreach ($currentOptions as $opt)
                            @php
                                $isSelected = $selectedOptionId === $opt['id'];
                                $isCorrectOption = $instantFeedbackEnabled && !empty($opt['is_correct']);
                                $showLockedState = $instantFeedbackEnabled && $currentAnswerLocked;
                                $optionClass = 'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900/30';

                                if ($showLockedState) {
                                    if ($isCorrectOption) {
                                        $optionClass = 'border-emerald-300 bg-emerald-50 text-emerald-950 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-100';
                                    } elseif ($isSelected && $currentAnswerIsCorrect === false) {
                                        $optionClass = 'border-red-300 bg-red-50 text-red-950 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-100';
                                    }
                                } elseif ($isSelected) {
                                    $optionClass = 'border-sky-300 bg-sky-50 dark:border-sky-900/50 dark:bg-sky-950/20';
                                }
                            @endphp
                            <label class="flex items-start gap-3 rounded-md border p-3 sm:p-4 {{ $optionClass }}">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2 text-sm font-semibold">
                                        <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-2 py-0.5 text-xs font-semibold text-zinc-700 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-200">{{ $opt['label'] }}</span>
                                        @if ($instantFeedbackEnabled && $currentAnswerLocked && !empty($opt['is_correct']))
                                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Benar</span>
                                        @elseif ($instantFeedbackEnabled && $currentAnswerLocked && $isSelected && $currentAnswerIsCorrect === false)
                                            <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-800">Pilihan Anda</span>
                                        @endif
                                    </div>
                                    @if (filled($opt['text']))
                                        <div class="text-sm text-zinc-700 dark:text-zinc-200 whitespace-pre-line">{{ $opt['text'] }}</div>
                                    @endif
                                    @if (!empty($opt['image_path']))
                                        <div class="@if(filled($opt['text'])) mt-3 @else mt-2 @endif">
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($opt['image_path']) }}" alt="Gambar opsi {{ $opt['label'] }}" class="max-h-48 rounded-md border border-zinc-200 object-contain dark:border-zinc-800" />
                                        </div>
                                    @endif
                                </div>
                                <input
                                    type="radio"
                                    name="selected_option"
                                    value="{{ $opt['id'] }}"
                                    class="mt-0.5 h-5 w-5 accent-blue-900"
                                    wire:model="selectedOptionId"
                                    @disabled($instantFeedbackEnabled && $currentAnswerLocked)
                                />
                            </label>
                        @endforeach
                    </div>
                    @if ($instantFeedbackEnabled && $currentAnswerLocked)
                        <div class="mt-3 rounded-md border border-sky-200 bg-sky-50 px-3 py-2 text-sm text-sky-900">
                            <div class="font-semibold">Terkunci</div>
                            <div class="mt-1">{{ $currentAnswerIsCorrect ? 'Jawaban Anda benar.' : 'Jawaban Anda salah. Opsi yang benar ditandai hijau.' }}</div>
                        </div>
                    @endif
                    @error('selectedOptionId')
                        <div class="mt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                @else
                    <div class="mt-4">
                        <label class="block text-sm font-medium mb-1">Jawaban</label>
                        <textarea wire:model.debounce.500ms="shortAnswerText" rows="3" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                        @error('shortAnswerText')
                            <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
            </div>

            <div class="mt-4 flex items-center justify-between gap-3">
                <div class="text-sm text-zinc-600 dark:text-zinc-300">Terjawab: {{ $answeredCount }}/{{ $totalQuestions }}</div>
                @php
                    $canAnswer = $currentQuestionType === 'multiple_choice'
                        ? (bool) $selectedOptionId
                        : (trim((string) $shortAnswerText) !== '');
                @endphp
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="skipCurrent"
                        @disabled($pendingAutoAdvance)
                        class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-100 disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:bg-zinc-800/40"
                    >
                        Skip
                    </button>
                    <button
                        type="button"
                        wire:click="answerCurrent"
                        @disabled(! $canAnswer)
                        class="rounded-md bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-50 disabled:hover:bg-blue-900"
                    >
                        Jawab
                    </button>
                </div>
            </div>
        </div>

        <script>
            window.addEventListener('participant-quiz-auto-advance', () => {
                window.setTimeout(() => {
                    try { @this.call('advanceAfterInstantFeedback'); } catch (e) {}
                }, 900);
            });
        </script>
    @endif
</div>
