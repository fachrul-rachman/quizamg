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
        <main class="min-h-screen flex items-center justify-center px-4 py-10">
            <div class="w-full max-w-3xl">
                {{ $slot }}
            </div>
        </main>

        @livewireScripts
    </body>
</html>
