<x-layouts.admin title="Generate Link">
    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="text-lg font-semibold mb-4">Generate Link</div>

    <form method="POST" action="{{ url('/admin/generate-link') }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
        @csrf

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1">Pilih Quiz</label>
                <select name="quiz_id" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" required>
                    <option value="">-- Pilih Quiz --</option>
                    @foreach ($activeQuizzes as $q)
                        <option value="{{ $q->id }}" @selected(old('quiz_id') == $q->id)>{{ $q->title }}</option>
                    @endforeach
                </select>
                @error('quiz_id')
                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Jumlah Link</label>
                <input name="count" value="{{ old('count', 1) }}" inputmode="numeric" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" required />
                @error('count')
                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <div class="block text-sm font-medium mb-1">Tipe Link</div>
            @php
                $usageType = old('usage_type', 'single');
            @endphp
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="radio" name="usage_type" value="single" @checked($usageType === 'single') />
                        <span>1 link 1 orang</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="radio" name="usage_type" value="multi" @checked($usageType === 'multi') />
                        <span>1 link banyak orang (wajib expired)</span>
                    </label>
                </div>
                @error('usage_type')
                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                @enderror
            </div>

            <div id="expiresHoursWrap" style="display: {{ $usageType === 'multi' ? 'block' : 'none' }};">
                <label class="block text-sm font-medium mb-1">Expired (jam)</label>
                <input id="expiresInHoursInput" name="expires_in_hours" value="{{ old('expires_in_hours') }}" inputmode="numeric" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('expires_in_hours')
                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="rounded-md bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Generate Link
            </button>
            <a href="{{ url('/admin/links') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Lihat Daftar Link
            </a>
        </div>
    </form>

    @if ($generatedLinks->isNotEmpty())
        <div class="mt-6 rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 text-sm font-semibold dark:border-zinc-800">
                <div>Hasil Generate</div>
                <button type="button" class="rounded-md border border-zinc-300 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40" onclick="copyAllGenerated()">
                    Copy All
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium"></th>
                            <th class="px-4 py-2 text-left font-medium">No</th>
                            <th class="px-4 py-2 text-left font-medium">Nama Quiz</th>
                            <th class="px-4 py-2 text-left font-medium">Tipe</th>
                            <th class="px-4 py-2 text-left font-medium">Expired</th>
                            <th class="px-4 py-2 text-left font-medium">Token</th>
                            <th class="px-4 py-2 text-left font-medium">URL Lengkap</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-left font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($generatedLinks as $idx => $link)
                                @php
                                    $baseUrl = rtrim((string) config('app.url'), '/');
                                    $url = $baseUrl !== '' ? $baseUrl.'/quiz/'.$link->token : url('/quiz/'.$link->token);
                                    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='.urlencode($url);
                                @endphp
                            <tr>
                                <td class="px-4 py-2">
                                    <input type="checkbox" class="generated-checkbox" value="{{ $url }}" checked />
                                </td>
                                <td class="px-4 py-2">{{ $idx + 1 }}</td>
                                <td class="px-4 py-2">{{ $link->quiz?->title ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $link->usage_type === 'multi' ? 'Multi-use' : 'Single-use' }}</td>
                                <td class="px-4 py-2">
                                    {{ optional($link->expires_at)->format('d M Y H:i') ?: '-' }}
                                </td>
                                <td class="px-4 py-2 font-mono">{{ $link->token }}</td>
                                <td class="px-4 py-2 font-mono">{{ $url }}</td>
                                <td class="px-4 py-2">{{ $link->status }}</td>
                                <td class="px-4 py-2">
                                    <button type="button" class="inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" onclick="copyText('{{ $url }}')">Copy</button>
                                    <a href="{{ $qrUrl }}" target="_blank" rel="noreferrer" class="ml-2 inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-900 hover:bg-blue-100">QR</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

        <script>
        function syncExpiresVisibility() {
            const wrap = document.getElementById('expiresHoursWrap');
            if (!wrap) return;
            const input = document.getElementById('expiresInHoursInput');
            const selected = document.querySelector('input[name="usage_type"]:checked');
            const isMulti = selected && selected.value === 'multi';
            wrap.style.display = isMulti ? 'block' : 'none';
            if (input) {
                input.disabled = !isMulti;
                if (!isMulti) {
                    input.value = '';
                }
            }
        }
        document.querySelectorAll('input[name="usage_type"]').forEach((el) => {
            el.addEventListener('change', syncExpiresVisibility);
        });
        syncExpiresVisibility();

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
        function copyAllGenerated() {
            const values = Array.from(document.querySelectorAll('.generated-checkbox'))
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            const text = values.join('\n');
            copyText(text);
        }
    </script>
</x-layouts.admin>
