<x-layouts.admin title="Admin Users">
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

    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-lg font-semibold">Admin Users</div>
        <a href="{{ url('/admin/users/create') }}" class="rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            Tambah User
        </a>
    </div>

    <form method="GET" action="{{ url('/admin/users') }}" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium mb-1">Search</label>
            <input name="search" value="{{ $search }}" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Role</label>
            <select name="role" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="all" @selected($role === 'all')>Semua</option>
                <option value="admin" @selected($role === 'admin')>admin</option>
                <option value="super_admin" @selected($role === 'super_admin')>super_admin</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="all" @selected($status === 'all')>Semua</option>
                <option value="active" @selected($status === 'active')>Aktif</option>
                <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
            </select>
        </div>
        <div class="sm:col-span-4 flex gap-2">
            <button type="submit" class="rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Filter
            </button>
            <a href="{{ url('/admin/users') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Reset
            </a>
        </div>
    </form>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        @if ($users->isEmpty())
            <div class="px-4 py-4 text-sm text-zinc-600 dark:text-zinc-300">Belum ada user admin.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Nama</th>
                            <th class="px-4 py-2 text-left font-medium">Email</th>
                            <th class="px-4 py-2 text-left font-medium">Role</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-left font-medium">Dibuat</th>
                            <th class="px-4 py-2 text-left font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($users as $user)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    <div class="font-semibold">{{ $user->name }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ $user->email }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        {{ $user->role }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $user->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-100 text-slate-700' }}">
                                        {{ $user->is_active ? 'aktif' : 'nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="text-sm font-medium">{{ optional($user->created_at)->format('d M Y') ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ optional($user->created_at)->format('H:i') ?: '' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ url('/admin/users/'.$user->id.'/edit') }}" class="inline-flex items-center rounded-md border border-amber-200 bg-amber-50 px-3 py-1.5 text-sm font-semibold text-amber-900 hover:bg-amber-100">
                                            Edit
                                        </a>
                                        <button type="button" class="inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-semibold text-rose-900 hover:bg-rose-100" onclick="document.getElementById('delete-user-{{ $user->id }}').showModal()">
                                            Hapus
                                        </button>
                                    </div>

                                    <dialog id="delete-user-{{ $user->id }}" class="rounded-lg border border-zinc-200 bg-white p-0 shadow-lg dark:border-zinc-800 dark:bg-zinc-950">
                                        <form method="dialog" class="p-4 border-b border-zinc-200 dark:border-zinc-800">
                                            <div class="text-sm font-semibold">Konfirmasi Hapus</div>
                                        </form>
                                        <div class="p-4 text-sm">
                                            User <span class="font-semibold">{{ $user->email }}</span> akan dihapus.
                                        </div>
                                        <div class="p-4 pt-0 flex items-center justify-end gap-2">
                                            <form method="dialog">
                                                <button class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">Batal</button>
                                            </form>
                                            <form method="POST" action="{{ url('/admin/users/'.$user->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500">Hapus</button>
                                            </form>
                                        </div>
                                    </dialog>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
