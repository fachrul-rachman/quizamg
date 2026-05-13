<x-layouts.admin title="Link Detail">
    @php
        $linkStatusLabel = fn (string $status): string => match ($status) {
            'unused' => 'Belum Dibuka',
            'opened' => 'Sudah Dibuka',
            'in_progress' => 'Sedang Dikerjakan',
            'submitted' => 'Selesai',
            'expired' => 'Kedaluwarsa',
            'not_started' => 'Belum Mulai',
            'auto_submitted' => 'Selesai Otomatis',
            default => $status,
        };
    @endphp
    @php
        $badgeBase = 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold';
    @endphp
    @php
        $linkStatusClass = fn (string $status): string => $badgeBase.' '.match ($status) {
            'unused' => 'border-slate-200 bg-slate-100 text-slate-700',
            'opened' => 'border-sky-200 bg-sky-50 text-sky-800',
            'in_progress' => 'border-amber-200 bg-amber-50 text-amber-800',
            'submitted' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'expired' => 'border-rose-200 bg-rose-50 text-rose-800',
            'not_started' => 'border-slate-200 bg-slate-100 text-slate-700',
            'auto_submitted' => 'border-orange-200 bg-orange-50 text-orange-800',
            default => 'border-slate-200 bg-slate-100 text-slate-700',
        };
    @endphp
    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-lg font-semibold">Link Detail</div>
        <a href="{{ url('/admin/links') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
            Kembali
        </a>
    </div>

            @php
                $baseUrl = rtrim((string) config('app.url'), '/');
                $url = $baseUrl !== '' ? $baseUrl.'/quiz/'.$link->token : url('/quiz/'.$link->token);
            @endphp

    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Nama Quiz</div>
                <div class="mt-1 font-semibold">{{ $link->quiz?->title ?? '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Tipe Link</div>
                <div class="mt-1 font-semibold">{{ $link->usage_type === 'multi' ? 'Multi-use' : 'Single-use' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Status</div>
                <div class="mt-1">
                    <span class="{{ $linkStatusClass((string) $link->status) }}">
                        {{ $linkStatusLabel((string) $link->status) }}
                    </span>
                </div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Expires At</div>
                <div class="mt-1">{{ optional($link->expires_at)->format('d M Y H:i:s') ?: '-' }}</div>
            </div>
            <div class="sm:col-span-2">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Token</div>
                <div class="mt-1 font-mono">{{ $link->token }}</div>
            </div>
            <div class="sm:col-span-2">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">URL Lengkap</div>
                <div class="mt-1 font-mono break-all">{{ $url }}</div>
                <button type="button" class="mt-2 inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" onclick="copyText('{{ $url }}')">Copy Link</button>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Opened At</div>
                <div class="mt-1">{{ optional($link->opened_at)->format('d M Y H:i:s') ?: '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Started At</div>
                <div class="mt-1">{{ optional($link->started_at)->format('d M Y H:i:s') ?: '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Submitted At</div>
                <div class="mt-1">{{ optional($link->submitted_at)->format('d M Y H:i:s') ?: '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Expired At</div>
                <div class="mt-1">{{ optional($link->expired_at)->format('d M Y H:i:s') ?: '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Dibuat Oleh</div>
                <div class="mt-1">{{ $link->creator?->name ?? '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Dibuat Pada</div>
                <div class="mt-1">{{ optional($link->created_at)->format('d M Y H:i:s') ?: '-' }}</div>
            </div>
        </div>
    </div>

    @if ($link->usage_type === 'multi')
        <div class="mt-4 rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
            <div class="border-b border-zinc-200 px-4 py-3 text-sm font-semibold dark:border-zinc-800">Daftar Attempt</div>
            @if (($link->attempts ?? collect())->isEmpty())
                <div class="px-4 py-4 text-sm text-zinc-600 dark:text-zinc-300">Belum ada attempt untuk link ini.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium">No</th>
                                <th class="px-4 py-2 text-left font-medium">Peserta</th>
                                <th class="px-4 py-2 text-left font-medium">Status</th>
                                <th class="px-4 py-2 text-left font-medium">Submitted</th>
                                <th class="px-4 py-2 text-left font-medium">Score</th>
                                <th class="px-4 py-2 text-left font-medium">Grade</th>
                                <th class="px-4 py-2 text-left font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach ($link->attempts as $idx => $attempt)
        @php
            $result = $attempt->result;
        @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 align-top">{{ $idx + 1 }}</td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-medium">{{ $attempt->participant_name }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $attempt->participant_applied_for ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <span class="{{ $linkStatusClass((string) $attempt->status) }}">
                                            {{ $linkStatusLabel((string) $attempt->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 align-top text-sm">
                                        <div class="font-medium">{{ optional($attempt->submitted_at)->format('d M Y') ?: '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ optional($attempt->submitted_at)->format('H:i:s') ?: '' }}</div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        @if ($result)
                                            <div class="font-medium">{{ number_format((float) $result->score_percentage, 2) }}%</div>
                                        @else
                                            <span class="text-zinc-500 dark:text-zinc-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        @if ($result)
                                            <div class="font-medium">Grade {{ $result->grade_letter }}{{ $result->grade_label ? ' - '.$result->grade_label : '' }}</div>
                                        @else
                                            <span class="text-zinc-500 dark:text-zinc-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        @if ($result)
                                            <a href="{{ url('/admin/results/'.$result->id) }}" class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-900 hover:bg-blue-100">
                                                Detail
                                            </a>
                                        @else
                                            <span class="text-zinc-500 dark:text-zinc-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @elseif ($link->attempt)
        <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm font-semibold mb-3">Data Attempt</div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Nama Peserta</div>
                <div class="mt-1 font-semibold">{{ $link->attempt->participant_name }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Jabatan</div>
                <div class="mt-1 font-semibold">{{ $link->attempt->participant_applied_for }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Status Attempt</div>
                <div class="mt-1">
                    <span class="{{ $linkStatusClass((string) $link->attempt->status) }}">
                        {{ $linkStatusLabel((string) $link->attempt->status) }}
                    </span>
                </div>
            </div>
                <div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Waktu Mulai</div>
                    <div class="mt-1">{{ optional($link->attempt->started_at)->format('d M Y H:i:s') ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Waktu Submit</div>
                    <div class="mt-1">{{ optional($link->attempt->submitted_at)->format('d M Y H:i:s') ?: '-' }}</div>
                </div>
            </div>
        </div>
    @endif

    <script>
        function fallbackCopyText(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.top = '-1000px';
            ta.style.left = '-1000px';
            document.body.appendChild(ta);
            ta.select();
            ta.setSelectionRange(0, ta.value.length);
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
        }
        function copyText(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).catch(() => fallbackCopyText(text));
                return;
            }
            fallbackCopyText(text);
        }
    </script>
</x-layouts.admin>
