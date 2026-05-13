@php
    $managedUser = $managedUser ?? null;
    $submitLabel = $submitLabel ?? 'Simpan';
@endphp

@if (session('error'))
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200">
        {{ session('error') }}
    </div>
@endif

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium mb-1">Nama</label>
        <input name="name" value="{{ old('name', $managedUser?->name) }}" required class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
        @error('name')
            <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
        @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input name="email" type="email" value="{{ old('email', $managedUser?->email) }}" required class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
        @error('email')
            <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
        @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Password {{ $managedUser ? '(kosongkan jika tidak diubah)' : '' }}</label>
        <x-password-input
            name="password"
            autocomplete="new-password"
            :required="! $managedUser"
            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950"
        />
        @error('password')
            <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
        @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Role</label>
        <select name="role" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
            <option value="admin" @selected(old('role', $managedUser?->role) === 'admin')>admin</option>
            <option value="super_admin" @selected(old('role', $managedUser?->role) === 'super_admin')>super_admin</option>
        </select>
        @error('role')
            <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
        @enderror
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium mb-1">Discord Webhook URL <span class="text-xs text-zinc-500">(opsional)</span></label>
        <input name="discord_webhook_url" value="{{ old('discord_webhook_url', $managedUser?->discord_webhook_url) }}" placeholder="https://discord.com/api/webhooks/xxx/xxx" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
        @error('discord_webhook_url')
            <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
        @enderror
    </div>
</div>

<label class="mt-4 inline-flex items-center gap-2 text-sm">
    <input type="hidden" name="is_active" value="0" />
    <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $managedUser?->is_active ?? true)) class="rounded border-zinc-300 dark:border-zinc-700" />
    <span>User aktif</span>
</label>
@error('is_active')
    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
@enderror

<div class="mt-6 flex items-center gap-2">
    <button type="submit" class="rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
        {{ $submitLabel }}
    </button>
    <a href="{{ url('/admin/users') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
        Batal
    </a>
</div>
