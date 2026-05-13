<x-layouts.admin title="Detail Hasil">
    @php
        $resultStatusLabel = fn (string $status): string => $status === 'auto_submitted' ? 'Selesai Otomatis' : 'Selesai';
        $badgeBase = 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold';
        $resultStatusClass = fn (string $status): string => $badgeBase.' '.($status === 'auto_submitted'
            ? 'border-orange-200 bg-orange-50 text-orange-800'
            : 'border-emerald-200 bg-emerald-50 text-emerald-800');

        $answerStatusLabel = fn (string $status): string => match ($status) {
            'correct' => 'Benar',
            'wrong' => 'Salah',
            'unanswered' => 'Belum Dijawab',
            default => $status,
        };

        $answerStatusClass = fn (string $status): string => $badgeBase.' '.match ($status) {
            'correct' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'wrong' => 'border-rose-200 bg-rose-50 text-rose-800',
            default => 'border-slate-200 bg-slate-100 text-slate-700',
        };
    @endphp
    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-lg font-semibold">Detail Hasil</div>
        <a href="{{ url('/admin/results') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
            Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="xl:col-span-2 space-y-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Nama</div>
                        <div class="mt-1 font-semibold">{{ $attempt->participant_name }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Jabatan</div>
                        <div class="mt-1 font-semibold">{{ $attempt->participant_applied_for ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Nama Quiz</div>
                        <div class="mt-1 font-semibold">{{ $quiz->title }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Status</div>
                        <div class="mt-1">
                            <span class="{{ $resultStatusClass((string) $result->result_status) }}">
                                {{ $resultStatusLabel((string) $result->result_status) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Mulai</div>
                        <div class="mt-1 font-semibold">{{ optional($attempt->started_at)->format('d M Y H:i:s') ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Submit</div>
                        <div class="mt-1 font-semibold">{{ optional($attempt->submitted_at)->format('d M Y H:i:s') ?: '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
                <div class="border-b border-zinc-200 px-4 py-3 text-sm font-semibold dark:border-zinc-800">Jawaban Peserta</div>
                <div class="space-y-4 p-4">
                    @foreach ($questionRows as $row)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="font-semibold">Soal {{ $row['no'] }}</div>
                                    @if ($quiz->difficulty_levels_enabled)
                                        @php
                                            $difficultyClass = match ($row['difficulty_level'] ?? '') {
                                                'mudah' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                                'sedang' => 'border-sky-200 bg-sky-50 text-sky-800',
                                                'sulit' => 'border-amber-200 bg-amber-50 text-amber-800',
                                                'sangat_sulit' => 'border-rose-200 bg-rose-50 text-rose-800',
                                                default => 'border-slate-200 bg-slate-100 text-slate-700',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $difficultyClass }}">
                                            {{ $row['difficulty_label'] ?? \App\Support\QuestionDifficulty::label($row['difficulty_level'] ?? null) }}
                                        </span>
                                    @endif
                                </div>
                                <span class="{{ $answerStatusClass((string) $row['status']) }}">
                                    {{ $answerStatusLabel((string) $row['status']) }}
                                </span>
                            </div>

                            @if ($row['question_text'] !== '')
                                <div class="mt-3 whitespace-pre-line text-sm">{{ $row['question_text'] }}</div>
                            @endif

                            @if ($row['question_image_url'])
                                <div class="mt-3">
                                    <img src="{{ $row['question_image_url'] }}" alt="Gambar soal {{ $row['no'] }}" class="max-h-72 rounded-md border border-zinc-200 object-contain dark:border-zinc-800" />
                                </div>
                            @endif

                            @if ($row['question_type'] === 'multiple_choice')
                                <div class="mt-4 space-y-3">
                                    @foreach ($row['options'] as $option)
                                        <div class="rounded-md border px-3 py-3 {{ $option['is_selected'] ? 'border-blue-300 bg-blue-50 dark:border-blue-900 dark:bg-blue-950/20' : 'border-zinc-200 dark:border-zinc-800' }}">
                                            <div class="flex flex-wrap items-center gap-2 text-sm font-semibold">
                                                <span>{{ $option['option_key'] }}</span>
                                                @if ($option['is_selected'])
                                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-800 dark:bg-blue-950/40 dark:text-blue-200">Dipilih</span>
                                                @endif
                                                @if ($option['is_correct'])
                                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">Benar</span>
                                                @endif
                                            </div>
                                            @if ($option['option_text'] !== '')
                                                <div class="mt-2 whitespace-pre-line text-sm">{{ $option['option_text'] }}</div>
                                            @endif
                                            @if ($option['option_image_url'])
                                                <div class="mt-3">
                                                    <img src="{{ $option['option_image_url'] }}" alt="Gambar opsi {{ $option['option_key'] }}" class="max-h-56 rounded-md border border-zinc-200 object-contain dark:border-zinc-800" />
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="rounded-md border border-zinc-200 p-3 dark:border-zinc-800">
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Jawaban Peserta</div>
                                        <div class="mt-2 whitespace-pre-line text-sm font-medium">{{ $row['participant_answer'] ?: '-' }}</div>
                                    </div>
                                    <div class="rounded-md border border-zinc-200 p-3 dark:border-zinc-800">
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Jawaban Benar</div>
                                        <div class="mt-2 text-sm">{{ $row['correct_answer'] ?: '-' }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-sm font-semibold">Ringkasan Nilai</div>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-zinc-500 dark:text-zinc-400">Score</span>
                        <span class="font-semibold">{{ number_format((float) $result->score_percentage, 2) }}%</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-zinc-500 dark:text-zinc-400">Grade</span>
                        <span class="font-semibold">{{ $result->grade_letter }}{{ $result->grade_label ? ' - '.$result->grade_label : '' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-zinc-500 dark:text-zinc-400">Benar</span>
                        <span class="font-semibold">{{ $result->correct_answers }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-zinc-500 dark:text-zinc-400">Salah</span>
                        <span class="font-semibold">{{ $result->wrong_answers }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-zinc-500 dark:text-zinc-400">Kosong</span>
                        <span class="font-semibold">{{ $result->unanswered_answers }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-zinc-500 dark:text-zinc-400">Dihitung</span>
                        <span class="font-semibold text-right">{{ optional($result->calculated_at)->format('d M Y H:i:s') ?: '-' }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-sm font-semibold">File Hasil</div>
                <div class="mt-4 space-y-3 text-sm">
                    <div>
                        <div class="text-zinc-500 dark:text-zinc-400">Nama File</div>
                        <div class="mt-1 font-medium break-all">{{ $pdf?->file_name ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="text-zinc-500 dark:text-zinc-400">Google Drive</div>
                        <div class="mt-1">
                            @if ($pdf?->google_drive_url)
                                <a href="{{ $pdf->google_drive_url }}" target="_blank" rel="noreferrer" class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-900 hover:bg-blue-100">Buka File di Google Drive</a>
                            @else
                                <span>-</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-zinc-500 dark:text-zinc-400">Generated At</div>
                        <div class="mt-1 font-medium">{{ optional($pdf?->generated_at)->format('d M Y H:i:s') ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="text-zinc-500 dark:text-zinc-400">Uploaded At</div>
                        <div class="mt-1 font-medium">{{ optional($pdf?->uploaded_at)->format('d M Y H:i:s') ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
