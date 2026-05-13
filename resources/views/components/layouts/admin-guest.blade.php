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
