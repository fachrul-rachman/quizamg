<x-layouts.admin title="Tambah Admin User">
    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-lg font-semibold">Tambah Admin User</div>
    </div>

    <form method="POST" action="{{ url('/admin/users') }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
        @csrf
        @include('admin.users._form', ['submitLabel' => 'Simpan User'])
    </form>
</x-layouts.admin>
