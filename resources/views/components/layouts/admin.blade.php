@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <script>
            try { window.localStorage.setItem('flux.appearance', 'light'); } catch (e) {}
            document.documentElement.classList.remove('dark');
        </script>
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 text-zinc-900">
        @php
            $user = auth()->user();
            $navLinkClass = function (bool $active): string {
                return $active
                    ? 'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold bg-blue-900 text-white'
                    : 'flex items-center gap-2 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100';
            };
        @endphp
        <input id="admin-mobile-nav" type="checkbox" class="peer sr-only" />

        <label for="admin-mobile-nav" class="lg:hidden fixed inset-0 z-40 bg-black/40 opacity-0 pointer-events-none transition peer-checked:opacity-100 peer-checked:pointer-events-auto"></label>

        <aside class="lg:hidden fixed inset-y-0 left-0 z-50 w-80 max-w-[85vw] -translate-x-full border-e border-zinc-200 bg-white transition peer-checked:translate-x-0">
                <div class="px-4 py-4 border-b border-zinc-200 flex items-center justify-between gap-3">
                    <a href="{{ url('/admin/dashboard') }}" class="inline-flex items-center gap-2">
                        <x-app-logo-icon class="h-9 w-9 object-contain" />
                        <span class="font-semibold">{{ config('app.name') }}</span>
                    </a>
                    <label for="admin-mobile-nav" class="cursor-pointer rounded-md p-2 hover:bg-slate-100" aria-label="Tutup menu">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </label>
                </div>

                <nav class="p-3 space-y-1">
                    <a href="{{ url('/admin/dashboard') }}" class="{{ $navLinkClass(request()->routeIs('admin.dashboard')) }}">
                        <span class="shrink-0">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-6v-7H10v7H4a1 1 0 0 1-1-1v-10.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ url('/admin/quizzes') }}" class="{{ $navLinkClass(request()->routeIs('admin.quizzes.*')) }}">
                        <span class="shrink-0">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 6a2 2 0 0 1 2-2h10l4 4v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 4v4h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M8 13h8M8 17h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>Quiz</span>
                    </a>
                    <a href="{{ url('/admin/generate-link') }}" class="{{ $navLinkClass(request()->routeIs('admin.links.*')) }}">
                        <span class="shrink-0">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M10 13a5 5 0 0 1 0-7l1-1a5 5 0 0 1 7 7l-1 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 11a5 5 0 0 1 0 7l-1 1a5 5 0 1 1-7-7l1-1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span>Generate Link</span>
                    </a>
                    <a href="{{ url('/admin/results') }}" class="{{ $navLinkClass(request()->routeIs('admin.results.*')) }}">
                        <span class="shrink-0">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 19V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M8 7h8M8 11h8M8 15h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>Hasil</span>
                    </a>
                    @if(($user?->role ?? null) === 'super_admin')
                        <a href="{{ url('/admin/users') }}" class="{{ $navLinkClass(request()->routeIs('admin.users.*')) }}">
                            <span class="shrink-0">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <span>Admin Users</span>
                        </a>
                    @endif
                    <form method="POST" action="{{ url('/logout') }}" class="pt-2">
                        @csrf
                        <button type="submit" class="w-full text-left flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold bg-red-600 text-white hover:bg-red-500">
                            <span class="shrink-0">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M10 17l-1 1a4 4 0 0 1-6-3V9a4 4 0 0 1 6-3l1 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M15 12H3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M15 12l-3-3M15 12l-3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M21 3h-8a2 2 0 0 0-2 2v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M11 16v3a2 2 0 0 0 2 2h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <span>Logout</span>
                        </button>
                    </form>
                </nav>
            </aside>

        <div class="min-h-screen lg:flex">
            <aside class="hidden lg:block lg:w-64 lg:flex-none border-e border-zinc-200 bg-white">
                <div class="px-4 py-4 border-b border-zinc-200">
                    <a href="{{ url('/admin/dashboard') }}" class="inline-flex items-center gap-2">
                        <x-app-logo-icon class="h-9 w-9 object-contain" />
                        <span class="font-semibold">{{ config('app.name') }}</span>
                    </a>
                </div>

                <nav class="p-3 space-y-1">
                    <a href="{{ url('/admin/dashboard') }}" class="{{ $navLinkClass(request()->routeIs('admin.dashboard')) }}">
                        <span class="shrink-0">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-6v-7H10v7H4a1 1 0 0 1-1-1v-10.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ url('/admin/quizzes') }}" class="{{ $navLinkClass(request()->routeIs('admin.quizzes.*')) }}">
                        <span class="shrink-0">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 6a2 2 0 0 1 2-2h10l4 4v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 4v4h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M8 13h8M8 17h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>Quiz</span>
                    </a>
                    <a href="{{ url('/admin/generate-link') }}" class="{{ $navLinkClass(request()->routeIs('admin.links.*')) }}">
                        <span class="shrink-0">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M10 13a5 5 0 0 1 0-7l1-1a5 5 0 0 1 7 7l-1 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 11a5 5 0 0 1 0 7l-1 1a5 5 0 1 1-7-7l1-1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span>Generate Link</span>
                    </a>
                    <a href="{{ url('/admin/results') }}" class="{{ $navLinkClass(request()->routeIs('admin.results.*')) }}">
                        <span class="shrink-0">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 19V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M8 7h8M8 11h8M8 15h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>Hasil</span>
                    </a>
                    @if(($user?->role ?? null) === 'super_admin')
                        <a href="{{ url('/admin/users') }}" class="{{ $navLinkClass(request()->routeIs('admin.users.*')) }}">
                            <span class="shrink-0">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <span>Admin Users</span>
                        </a>
                    @endif
                    <form method="POST" action="{{ url('/logout') }}" class="pt-2">
                        @csrf
                        <button type="submit" class="w-full text-left flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold bg-red-600 text-white hover:bg-red-500">
                            <span class="shrink-0">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M10 17l-1 1a4 4 0 0 1-6-3V9a4 4 0 0 1 6-3l1 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M15 12H3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M15 12l-3-3M15 12l-3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M21 3h-8a2 2 0 0 0-2 2v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M11 16v3a2 2 0 0 0 2 2h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <span>Logout</span>
                        </button>
                    </form>
                </nav>
            </aside>

            <div class="flex-1 min-w-0">
                <header class="border-b border-zinc-200 bg-white">
                    <div class="px-4 py-3 flex items-center gap-3">
                        <label for="admin-mobile-nav" class="lg:hidden cursor-pointer select-none rounded-md p-2 hover:bg-slate-100" aria-label="Buka menu">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </label>

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

        @livewireScripts
        <script>
            (function () {
                const disabledKey = 'data-disable-once-applied';
                const disableStyles = 'pointer-events:none;opacity:.55;cursor:not-allowed;';

                function markDisabled(el) {
                    if (!el || el.getAttribute(disabledKey) === '1') return;
                    el.setAttribute(disabledKey, '1');

                    if (el.tagName === 'BUTTON') {
                        el.disabled = true;
                        return;
                    }

                    if (el.tagName === 'INPUT') {
                        el.disabled = true;
                        return;
                    }

                    if (el.tagName === 'A') {
                        el.setAttribute('aria-disabled', 'true');
                        el.setAttribute('tabindex', '-1');
                        const existing = el.getAttribute('style') || '';
                        el.setAttribute('style', existing + (existing.endsWith(';') || existing === '' ? '' : ';') + disableStyles);
                    }
                }

                document.addEventListener('submit', function (e) {
                    const form = e.target;
                    if (!form || form.nodeName !== 'FORM') return;
                    form.querySelectorAll('button, input[type="submit"]').forEach(markDisabled);
                }, true);

                document.addEventListener('click', function (e) {
                    const el = e.target && e.target.closest ? e.target.closest('button, a, input[type="submit"]') : null;
                    if (!el) return;
                    if (el.hasAttribute('data-disable-once-exempt')) return;
                    if (el.hasAttribute('disabled') || el.getAttribute(disabledKey) === '1') return;
                    if (el.getAttribute('aria-disabled') === 'true') return;

                    setTimeout(function () { markDisabled(el); }, 0);
                }, true);
            })();
        </script>
    </body>
</html>
