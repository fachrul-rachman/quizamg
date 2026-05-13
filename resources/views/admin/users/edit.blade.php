<x-layouts.admin title="Edit Admin User">
    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-lg font-semibold">Edit Admin User</div>
    </div>

    <form method="POST" action="{{ url('/admin/users/'.$managedUser->id) }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
        @csrf
        @method('PUT')
        @include('admin.users._form', ['managedUser' => $managedUser, 'submitLabel' => 'Simpan Perubahan'])
    </form>
</x-layouts.admin>
