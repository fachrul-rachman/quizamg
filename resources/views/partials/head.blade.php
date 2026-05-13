<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'quizzes') : config('app.name', 'quizzes') }}
</title>

@php
    $faviconVersion = file_exists(public_path('favicon.ico'))
        ? filemtime(public_path('favicon.ico'))
        : time();
@endphp

<link rel="icon" href="/favicon.ico?v={{ $faviconVersion }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v={{ $faviconVersion }}">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v={{ $faviconVersion }}">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v={{ $faviconVersion }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
