<x-layouts.admin-guest>
    <h1 class="text-xl font-semibold mb-1">Login Admin</h1>

    @if (session('error'))
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    @if (session('status'))
        <div class="mt-4 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900/40">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ url('/admin/login') }}" class="mt-5 space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium mb-1">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 dark:border-zinc-700 dark:bg-zinc-950 dark:focus:ring-zinc-200"
            />
            @error('email')
                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium mb-1">Password</label>
            <x-password-input
                id="password"
                name="password"
                required
                autocomplete="current-password"
            />
            @error('password')
                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="w-full rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            Login
        </button>
    </form>
</x-layouts.admin-guest>
