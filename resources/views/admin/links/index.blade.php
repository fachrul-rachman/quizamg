<x-layouts.admin title="Daftar Link">
    @php
        $linkStatusLabel = fn (string $status): string => match ($status) {
            'unused' => 'Belum Dibuka',
            'opened' => 'Sudah Dibuka',
            'in_progress' => 'Sedang Dikerjakan',
            'submitted' => 'Selesai',
            'expired' => 'Kedaluwarsa',
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
            default => 'border-slate-200 bg-slate-100 text-slate-700',
        };
    @endphp
    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-lg font-semibold">Daftar Link</div>
        <a href="{{ url('/admin/generate-link') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
            Generate Link
        </a>
    </div>

    <form method="GET" action="{{ url('/admin/links') }}" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium mb-1">Search Token</label>
            <input name="search" value="{{ $search }}" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Quiz</label>
            <select name="quiz_id" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="">Semua</option>
                @foreach ($quizzes as $q)
                    <option value="{{ $q->id }}" @selected((string) $q->id === (string) $quizId)>{{ $q->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="all" @selected($status === 'all')>Semua</option>
                <option value="unused" @selected($status === 'unused')>Belum Dibuka</option>
                <option value="opened" @selected($status === 'opened')>Sudah Dibuka</option>
                <option value="in_progress" @selected($status === 'in_progress')>Sedang Dikerjakan</option>
                <option value="submitted" @selected($status === 'submitted')>Selesai</option>
                <option value="expired" @selected($status === 'expired')>Kedaluwarsa</option>
            </select>
        </div>

        <div class="sm:col-span-4 flex gap-2">
            <button type="submit" class="rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Filter
            </button>
            <a href="{{ url('/admin/links') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Reset
            </a>
        </div>
    </form>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        @if ($links->isEmpty())
            <div class="px-4 py-4 text-sm text-zinc-600 dark:text-zinc-300">Belum ada link.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Nama Quiz</th>
                            <th class="px-4 py-2 text-left font-medium">Tipe</th>
                            <th class="px-4 py-2 text-left font-medium">Expired</th>
                            <th class="px-4 py-2 text-left font-medium">Attempt</th>
                            <th class="px-4 py-2 text-left font-medium">Token</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-left font-medium">Opened At</th>
                            <th class="px-4 py-2 text-left font-medium">Started At</th>
                            <th class="px-4 py-2 text-left font-medium">Submitted At</th>
                            <th class="px-4 py-2 text-left font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($links as $link)
                                @php
                                    $baseUrl = rtrim((string) config('app.url'), '/');
                                    $url = $baseUrl !== '' ? $baseUrl.'/quiz/'.$link->token : url('/quiz/'.$link->token);
                                    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='.urlencode($url);
                                @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    <div class="font-semibold">{{ $link->quiz?->title ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        {{ $link->usage_type === 'multi' ? 'Multi-use' : 'Single-use' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top text-sm">
                                    <div class="font-medium">{{ optional($link->expires_at)->format('d M Y') ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ optional($link->expires_at)->format('H:i:s') ?: '' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        {{ (int) ($link->attempts_count ?? 0) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top font-mono text-xs text-slate-700">{{ $link->token }}</td>
                                <td class="px-4 py-3 align-top">
                                    <span class="{{ $linkStatusClass((string) $link->status) }}">
                                        {{ $linkStatusLabel((string) $link->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top text-sm">
                                    <div class="font-medium">{{ optional($link->opened_at)->format('d M Y') ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ optional($link->opened_at)->format('H:i:s') ?: '' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top text-sm">
                                    <div class="font-medium">{{ optional($link->started_at)->format('d M Y') ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ optional($link->started_at)->format('H:i:s') ?: '' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top text-sm">
                                    <div class="font-medium">{{ optional($link->submitted_at)->format('d M Y') ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ optional($link->submitted_at)->format('H:i:s') ?: '' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-center gap-3">
                                        <button type="button" class="inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" onclick="copyText('{{ $url }}')">Copy Link</button>
                                        <a href="{{ $qrUrl }}" target="_blank" rel="noreferrer" class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-900 hover:bg-blue-100">QR</a>
                                        <a href="{{ url('/admin/links/'.$link->id) }}" class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-900 hover:bg-blue-100">Detail</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3">
                {{ $links->links() }}
            </div>
        @endif
    </div>

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
