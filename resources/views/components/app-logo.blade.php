@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="{{ config('app.name', 'Quizzes') }}" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-10 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-9 object-contain" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="{{ config('app.name', 'Quizzes') }}" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-10 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-9 object-contain" />
        </x-slot>
    </flux:brand>
@endif
