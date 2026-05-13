@props([
    'id' => null,
    'name',
])

@php
    $inputId = $id ?? $name;
    $providedClass = $attributes->get('class');
    $inputClass = trim(
        ($providedClass ?: 'w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 dark:border-zinc-700 dark:bg-zinc-950 dark:focus:ring-zinc-200')
        .' pr-12'
    );
@endphp

<div class="relative" data-password-field>
    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="password"
        data-password-input
        {{ $attributes->except('class')->merge(['class' => $inputClass]) }}
    />

    <button
        type="button"
        class="absolute inset-y-0 end-0 z-10 flex items-center px-3 text-zinc-500 hover:text-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-900 dark:hover:text-zinc-200 dark:focus-visible:ring-zinc-200"
        data-password-toggle
        data-disable-once-exempt
        data-password-label-show="{{ __('Show password') }}"
        data-password-label-hide="{{ __('Hide password') }}"
        aria-label="{{ __('Show password') }}"
    >
        <span class="sr-only" data-password-label>{{ __('Show password') }}</span>

        <svg data-password-icon="show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="pointer-events-none h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>

        <svg data-password-icon="hide" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hidden pointer-events-none h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 002.036 12.322a1.012 1.012 0 000 .639C3.423 16.49 7.36 19.5 12 19.5c1.773 0 3.461-.441 4.98-1.223" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.228 6.228A10.45 10.45 0 0112 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639a10.523 10.523 0 01-4.293 5.774" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.228 6.228l11.444 11.444" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 9.88a3 3 0 104.243 4.243" />
        </svg>
    </button>
</div>
