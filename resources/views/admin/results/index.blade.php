<x-layouts.admin title="Daftar Hasil">
    @php
        $resultStatusLabel = fn (string $status): string => $status === 'auto_submitted' ? 'Selesai Otomatis' : 'Selesai';
        $badgeBase = 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold';
    @endphp
    @php
        $resultStatusClass = fn (string $status): string => $badgeBase.' '.($status === 'auto_submitted'
            ? 'border-orange-200 bg-orange-50 text-orange-800'
            : 'border-emerald-200 bg-emerald-50 text-emerald-800');
    @endphp
    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-lg font-semibold">Daftar Hasil</div>
    </div>

    <form method="GET" action="{{ url('/admin/results') }}" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-6">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium mb-1">Search</label>
            <input name="search" value="{{ $search }}" placeholder="Nama peserta, jabatan, atau nama quiz" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Quiz</label>
            <select name="quiz_id" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="">Semua</option>
                @foreach ($quizzes as $quiz)
                    <option value="{{ $quiz->id }}" @selected((string) $quiz->id === (string) $quizId)>{{ $quiz->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="all" @selected($status === 'all')>Semua</option>
                <option value="submitted" @selected($status === 'submitted')>Selesai</option>
                <option value="auto_submitted" @selected($status === 'auto_submitted')>Selesai Otomatis</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Periode</label>
            <select name="range" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="" @selected($rangePreset === '')>Custom</option>
                <option value="week" @selected($rangePreset === 'week')>1 Minggu Terakhir</option>
                <option value="month" @selected($rangePreset === 'month')>1 Bulan Terakhir</option>
                <option value="year" @selected($rangePreset === 'year')>1 Tahun Terakhir</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Jabatan</label>
            <select name="jabatan" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="">Semua</option>
                @foreach ($jabatanOptions as $opt)
                    <option value="{{ $opt }}" @selected($jabatan === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-3">
            <label class="block text-sm font-medium mb-1">Tanggal Submit (Mulai)</label>
            <input name="date_from" type="date" value="{{ $dateFrom }}" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
        </div>
        <div class="sm:col-span-3">
            <label class="block text-sm font-medium mb-1">Tanggal Submit (Sampai)</label>
            <input name="date_to" type="date" value="{{ $dateTo }}" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
        </div>
        <div class="sm:col-span-6 flex gap-2">
            <button type="submit" class="rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Filter
            </button>
            <a href="{{ url('/admin/results/export') }}?{{ http_build_query(request()->except('page')) }}" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-900 hover:bg-emerald-100">
                Export Excel
            </a>
            <a href="{{ url('/admin/results') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Reset
            </a>
        </div>
    </form>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        @if ($rows->isEmpty())
            <div class="px-4 py-4 text-sm text-zinc-600 dark:text-zinc-300">Belum ada hasil quiz.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Peserta</th>
                            <th class="px-4 py-2 text-left font-medium">Quiz</th>
                            <th class="px-4 py-2 text-left font-medium">Score</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-left font-medium">Tanggal</th>
                            <th class="px-4 py-2 text-left font-medium">File Drive</th>
                            <th class="px-4 py-2 text-left font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($rows as $row)
                    @php
                        $result = $row['result'];
                        $attempt = $result->attempt;
                        $pdf = $row['pdf'];
                    @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ $attempt?->participant_name ?? '-' }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $attempt?->participant_applied_for ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="font-semibold">{{ $result->quiz?->title ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ number_format((float) $result->score_percentage, 2) }}%</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $result->correct_answers }}/{{ $result->total_questions }} benar | Grade {{ $result->grade_letter ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="{{ $resultStatusClass((string) $result->result_status) }}">
                                        {{ $resultStatusLabel((string) $result->result_status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="text-sm font-medium">{{ optional($result->calculated_at)->format('d M Y') ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ optional($result->calculated_at)->format('H:i:s') ?: '' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @if ($pdf?->google_drive_url)
                                        <a href="{{ $pdf->google_drive_url }}" target="_blank" rel="noreferrer" class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-900 hover:bg-blue-100">
                                            Buka File
                                        </a>
                                    @else
                                        <span class="text-zinc-500 dark:text-zinc-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <a href="{{ url('/admin/results/'.$result->id) }}" class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-900 hover:bg-blue-100">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3">
                {{ $results->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
