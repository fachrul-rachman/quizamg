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
        <main class="min-h-screen flex items-center justify-center px-4 py-10">
            <div class="w-full max-w-md">
                <div class="flex items-center justify-center mb-6">
                    <a href="{{ url('/admin/login') }}" class="inline-flex items-center gap-2">
                        <x-app-logo-icon class="h-12 w-12 object-contain" />
                        <span class="font-semibold text-lg">{{ config('app.name') }}</span>
                    </a>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </body>
</html>
