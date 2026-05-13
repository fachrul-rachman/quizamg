<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <script>
            try { window.localStorage.setItem('flux.appearance', 'light'); } catch (e) {}
            document.documentElement.classList.remove('dark');
        </script>
    </head>
    <body class="min-h-screen bg-slate-50 text-zinc-900">
    @php
        $user = auth()->user();
    @endphp
        <div class="min-h-screen lg:flex">
            <aside class="hidden lg:block lg:w-64 lg:flex-none border-e border-zinc-200 bg-white">
                <div class="px-4 py-4 border-b border-zinc-200">
                    <a href="{{ url('/admin/dashboard') }}" class="inline-flex items-center gap-2">
                        <x-app-logo-icon class="h-9 w-9 object-contain" />
                        <span class="font-semibold">{{ config('app.name') }}</span>
                    </a>
                </div>

                <nav class="p-3 space-y-1">
                    <a href="{{ url('/admin/dashboard') }}" class="block rounded-md px-3 py-2 text-sm hover:bg-slate-100">Dashboard</a>
                    <a href="{{ url('/admin/quizzes') }}" class="block rounded-md px-3 py-2 text-sm hover:bg-slate-100">Quiz</a>
                    <a href="{{ url('/admin/generate-link') }}" class="block rounded-md px-3 py-2 text-sm hover:bg-slate-100">Generate Link</a>
                    <a href="{{ url('/admin/results') }}" class="block rounded-md px-3 py-2 text-sm hover:bg-slate-100">Hasil</a>
                    @if(($user?->role ?? null) === 'super_admin')
                        <a href="{{ url('/admin/users') }}" class="block rounded-md px-3 py-2 text-sm hover:bg-slate-100">Admin Users</a>
                    @endif
                    <form method="POST" action="{{ url('/logout') }}" class="pt-2">
                        @csrf
                        <button type="submit" class="w-full text-left rounded-md px-3 py-2 text-sm font-semibold bg-red-600 text-white hover:bg-red-500">Logout</button>
                    </form>
                </nav>
            </aside>

            <div class="flex-1 min-w-0">
                <header class="border-b border-zinc-200 bg-white">
                    <div class="px-4 py-3 flex items-center gap-3">
                        <details class="lg:hidden">
                            <summary class="cursor-pointer select-none rounded-md px-3 py-2 text-sm hover:bg-slate-100">
                                Menu
                            </summary>
                            <div class="mt-2 rounded-md border border-zinc-200 bg-white p-2 shadow-sm">
                                <a href="{{ url('/admin/dashboard') }}" class="block rounded-md px-3 py-2 text-sm hover:bg-slate-100">Dashboard</a>
                                <a href="{{ url('/admin/quizzes') }}" class="block rounded-md px-3 py-2 text-sm hover:bg-slate-100">Quiz</a>
                                <a href="{{ url('/admin/generate-link') }}" class="block rounded-md px-3 py-2 text-sm hover:bg-slate-100">Generate Link</a>
                                <a href="{{ url('/admin/results') }}" class="block rounded-md px-3 py-2 text-sm hover:bg-slate-100">Hasil</a>
                                @if(($user?->role ?? null) === 'super_admin')
                                    <a href="{{ url('/admin/users') }}" class="block rounded-md px-3 py-2 text-sm hover:bg-slate-100">Admin Users</a>
                                @endif
                                <form method="POST" action="{{ url('/logout') }}" class="pt-1">
                                    @csrf
                                    <button type="submit" class="w-full text-left rounded-md px-3 py-2 text-sm font-semibold bg-red-600 text-white hover:bg-red-500">Logout</button>
                                </form>
                            </div>
                        </details>

                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold truncate">{{ $title ?? '' }}</div>
                        </div>

                        <div class="flex items-center gap-3 text-sm">
                            <div class="text-right leading-tight">
                                <div class="font-medium">{{ $user?->name ?? 'Guest' }}</div>
                                <div class="text-xs text-zinc-500">{{ $user?->role ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="p-4">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
