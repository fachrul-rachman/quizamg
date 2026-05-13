<div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
    @if ($state === 'invalid')
        <h1 class="text-xl font-semibold">Link Quiz Tidak Valid</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Link yang Anda buka tidak ditemukan atau sudah tidak berlaku. Periksa kembali link dari admin.</div>
    @elseif ($state === 'final')
        <h1 class="text-xl font-semibold">Link Quiz Tidak Bisa Digunakan</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $finalMessage }}</div>
    @elseif ($state === 'unavailable')
        <h1 class="text-xl font-semibold">Quiz tidak tersedia.</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Quiz sedang nonaktif atau belum siap digunakan. Hubungi admin untuk link pengganti.</div>
    @elseif ($state === 'start')
        @if (session('success'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-4">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Nama Quiz</div>
            <div class="mt-1 text-lg font-semibold">{{ $title }}</div>
            <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Durasi: {{ $durationMinutes }} menit</div>
        </div>

        <div class="mb-4 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-100">
            <div class="font-semibold">Sebelum mulai</div>
            <ul class="mt-2 space-y-1.5 text-sm">
                <li class="flex gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-blue-900">✓</span>
                    <span>Timer mulai saat Anda menekan tombol <span class="font-semibold">Mulai Test</span>.</span>
                </li>
                <li class="flex gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-blue-900">✓</span>
                    <span>Tidak ada tombol kembali atau nomor soal. Setelah klik <span class="font-semibold">Jawab</span>, otomatis lanjut ke soal berikutnya.</span>
                </li>
                <li class="flex gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-blue-900">✓</span>
                    <span>Jawaban yang sudah dikirim per soal dianggap final (tidak bisa diubah).</span>
                </li>
                <li class="flex gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-blue-900">✓</span>
                    <span>Jika waktu habis, test akan selesai otomatis.</span>
                </li>
                <li class="flex gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-blue-900">✓</span>
                    <span>Anda bisa klik <span class="font-semibold">Simpan Identitas</span> dulu. Test belum berjalan sampai tombol mulai ditekan.</span>
                </li>
            </ul>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium mb-1">Nama Peserta</label>
                <input wire:model.defer="participantName" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('participantName')
                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Jabatan</label>
                <input wire:model.defer="participantAppliedFor" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('participantAppliedFor')
                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mt-5 flex items-center gap-2">
            <button type="button" wire:click="startTest" class="rounded-md bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                Mulai Test
            </button>
            <button type="button" wire:click="saveIdentity" class="rounded-md border border-zinc-300 px-4 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Simpan Identitas
            </button>
        </div>
    @endif
</div>
