<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <main class="mx-auto flex min-h-screen max-w-5xl flex-col items-center justify-center px-6 py-16 text-center">
            <div class="mb-6 flex items-center justify-center">
                <x-app-logo-icon class="h-32 w-32 object-contain sm:h-40 sm:w-40" />
            </div>

            <h1 class="text-4xl font-semibold tracking-tight text-neutral-900 dark:text-neutral-50 sm:text-5xl">
                {{ config('app.name', 'Quizzes') }}
            </h1>
            <p class="mt-3 max-w-xl text-base text-neutral-600 dark:text-neutral-300 sm:text-lg">
                Buat, kelola, dan jalankan quiz dengan cepat — semuanya dalam satu dashboard.
            </p>

            <div class="mt-8 flex items-center justify-center gap-3">
                <a
                    href="{{ route('admin.login') }}"
                    class="inline-flex items-center justify-center rounded-md bg-neutral-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-neutral-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-900 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200"
                >
                    Login Admin
                </a>
            </div>
        </main>
    </body>
</html>

