<div class="space-y-4">
    <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <h1 class="text-xl font-semibold">Jawaban Berhasil Dikirim</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Terima kasih. Jawaban Anda sudah tercatat.</div>
    </div>

    @if ($showResult && is_array($summary))
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">Hasil Quiz</div>
            <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Score</div>
                    <div class="mt-1 text-3xl font-semibold">{{ $summary['score_percentage'] }}%</div>
                </div>
                <div class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-800">
                    Grade {{ $summary['grade_letter'] }} - {{ $summary['grade_label'] }}
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-md border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Benar</div>
                    <div class="mt-1 text-xl font-semibold">{{ $summary['correct_answers'] }} / {{ $summary['total_questions'] }}</div>
                </div>
                <div class="rounded-md border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Salah</div>
                    <div class="mt-1 text-xl font-semibold">{{ $summary['wrong_answers'] }}</div>
                </div>
                <div class="rounded-md border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Tidak Dijawab</div>
                    <div class="mt-1 text-xl font-semibold">{{ $summary['unanswered_answers'] }}</div>
                </div>
            </div>
        </div>
    @endif
</div>
