<x-layouts.admin title="Detail Quiz">
    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-lg font-semibold">Detail Quiz</div>
        <div class="flex items-center gap-2">
            <a href="{{ url('/admin/quizzes/'.$quiz->id.'/edit') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Edit
            </a>
            <a href="{{ url('/admin/quizzes') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Kembali
            </a>
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Nama Quiz</div>
                <div class="mt-1 font-semibold">{{ $quiz->title }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Durasi</div>
                <div class="mt-1 font-semibold">{{ $quiz->duration_minutes }} menit</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Jumlah Soal</div>
                <div class="mt-1 font-semibold">{{ $quiz->questions->count() }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Shuffle Soal</div>
                <div class="mt-1 font-semibold">{{ $quiz->shuffle_questions ? 'Ya' : 'Tidak' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Shuffle Opsi</div>
                <div class="mt-1 font-semibold">{{ $quiz->shuffle_options ? 'Ya' : 'Tidak' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Tampilkan Jawaban Benar</div>
                <div class="mt-1 font-semibold">{{ $quiz->instant_feedback_enabled ? 'Ya' : 'Tidak' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Kesulitan Bertingkat</div>
                <div class="mt-1 font-semibold">{{ $quiz->difficulty_levels_enabled ? 'Ya' : 'Tidak' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Status</div>
                <div class="mt-1">
                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $quiz->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-100 text-slate-700' }}">
                        {{ $quiz->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Dibuat Oleh</div>
                <div class="mt-1 font-semibold">{{ $quiz->creator?->name ?? '-' }}</div>
            </div>
        </div>

        @if (filled($quiz->description))
            <div class="mt-4">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Deskripsi</div>
                <div class="mt-1 text-sm whitespace-pre-line">{{ $quiz->description }}</div>
            </div>
        @endif
    </div>

    <div class="mt-4 rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div class="border-b border-zinc-200 px-4 py-3 text-sm font-semibold dark:border-zinc-800">Soal</div>
        @if ($quiz->questions->isEmpty())
            <div class="px-4 py-4 text-sm text-zinc-600 dark:text-zinc-300">Belum ada soal.</div>
        @else
            <div class="space-y-4 p-4">
                @foreach ($quiz->questions as $q)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="font-semibold">Soal {{ $q->order_number }}</div>
                                @if ($quiz->difficulty_levels_enabled)
                                    @php
                                        $difficultyClass = match ($q->difficulty_level ?? '') {
                                            'mudah' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                            'sedang' => 'border-sky-200 bg-sky-50 text-sky-800',
                                            'sulit' => 'border-amber-200 bg-amber-50 text-amber-800',
                                            'sangat_sulit' => 'border-rose-200 bg-rose-50 text-rose-800',
                                            default => 'border-slate-200 bg-slate-100 text-slate-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $difficultyClass }}">
                                        {{ \App\Support\QuestionDifficulty::label($q->difficulty_level) }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $q->question_type === 'short_answer' ? 'Short Answer' : 'Multiple Choice' }} | {{ $q->is_active ? 'Aktif' : 'Nonaktif' }}
                            </div>
                        </div>

                        @if (filled($q->question_text))
                            <div class="mt-3 whitespace-pre-line text-sm">{{ $q->question_text }}</div>
                        @endif

                        @if ($q->question_image_path)
                            <div class="mt-3">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($q->question_image_path) }}" alt="Gambar soal {{ $q->order_number }}" class="max-h-72 rounded-md border border-zinc-200 object-contain dark:border-zinc-800" />
                            </div>
                        @endif

                        @if ($q->question_type === 'multiple_choice')
                            <div class="mt-4 space-y-3">
                                @foreach ($q->options as $opt)
                                    <div class="rounded-md border border-zinc-200 p-3 dark:border-zinc-800">
                                        <div class="text-sm font-semibold">
                                            {{ $opt->option_key }} @if($opt->is_correct) - Benar @endif
                                        </div>
                                        @if (filled($opt->option_text))
                                            <div class="mt-2 whitespace-pre-line text-sm">{{ $opt->option_text }}</div>
                                        @endif
                                        @if ($opt->option_image_path)
                                            <div class="mt-3">
                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($opt->option_image_path) }}" alt="Gambar opsi {{ $opt->option_key }}" class="max-h-56 rounded-md border border-zinc-200 object-contain dark:border-zinc-800" />
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-4">
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">Jawaban benar</div>
                                <div class="mt-2 text-sm">{{ $q->shortAnswerKeys->pluck('answer_text')->implode(' | ') }}</div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.admin>
