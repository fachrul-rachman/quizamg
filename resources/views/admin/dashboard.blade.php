<x-layouts.admin title="Dashboard">
    @php
        $resultStatusLabel = fn (string $status): string => $status === 'auto_submitted' ? 'Selesai Otomatis' : 'Selesai';
        $badgeBase = 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold';
    @endphp
    @php
        $resultStatusClass = fn (string $status): string => $badgeBase.' '.($status === 'auto_submitted'
            ? 'border-orange-200 bg-orange-50 text-orange-800'
            : 'border-emerald-200 bg-emerald-50 text-emerald-800');
    @endphp
    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    @if (($googleDrive['auth_mode'] ?? '') === 'oauth' && ! ($googleDrive['oauth_connected'] ?? false))
        <div class="mb-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-sm font-semibold">Google Drive OAuth</div>
                    <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                        Status: {{ ($googleDrive['oauth_connected'] ?? false) ? 'Connected' : 'Belum connect' }}
                    </div>
                    <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                        Folder ID: {{ ($googleDrive['folder_id'] ?? '') !== '' ? $googleDrive['folder_id'] : 'Belum diisi' }}
                    </div>
                </div>
                <div>
                    <a href="{{ url('/admin/integrations/google-drive/connect') }}" class="inline-flex rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                        Connect Google Drive
                    </a>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 {{ auth()->user()?->role === 'super_admin' ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }}">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Quiz</div>
            <div class="mt-1 text-2xl font-semibold">{{ $stats['total_quizzes'] }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Link Generated</div>
            <div class="mt-1 text-2xl font-semibold">{{ $stats['total_links'] }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Hasil Masuk</div>
            <div class="mt-1 text-2xl font-semibold">{{ $stats['total_results'] }}</div>
        </div>
        @if (auth()->user()?->role === 'super_admin')
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Admin User</div>
                <div class="mt-1 text-2xl font-semibold">{{ $stats['total_admin_users'] }}</div>
            </div>
        @endif
    </div>

    <div class="mt-4 rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div class="border-b border-zinc-200 px-4 py-3 text-sm font-semibold dark:border-zinc-800">Hasil Terbaru</div>

        @if ($latestResults->count() === 0)
            <div class="px-4 py-4 text-sm text-zinc-600 dark:text-zinc-300">Belum ada hasil quiz.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Nama Test</th>
                            <th class="px-4 py-2 text-left font-medium">Nama Peserta</th>
                            <th class="px-4 py-2 text-left font-medium">Jabatan</th>
                            <th class="px-4 py-2 text-left font-medium">Score</th>
                            <th class="px-4 py-2 text-left font-medium">Grade</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-left font-medium">Waktu Selesai</th>
                            <th class="px-4 py-2 text-left font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($latestResults as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    <div class="font-semibold">{{ $row->quiz_title }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ $row->participant_name }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="text-sm">{{ $row->participant_applied_for }}</div>
                                </td>
                                <td class="px-4 py-2">{{ number_format((float) $row->score_percentage, 2) }}%</td>
                                <td class="px-4 py-2">{{ $row->grade_letter }} - {{ $row->grade_label }}</td>
                                <td class="px-4 py-2">
                                    <span class="{{ $resultStatusClass((string) $row->result_status) }}">
                                        {{ $resultStatusLabel((string) $row->result_status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top">
                        @php
                            $calculatedAt = \Illuminate\Support\Carbon::parse($row->calculated_at);
                        @endphp
                                    <div class="text-sm font-medium">{{ $calculatedAt->format('d M Y') }}</div>
                                    <div class="text-xs text-slate-500">{{ $calculatedAt->format('H:i:s') }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <a href="{{ url('/admin/results/'.$row->id) }}" class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-900 hover:bg-blue-100">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
                {{ $latestResults->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
