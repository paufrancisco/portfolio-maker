<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'CampusCraft') }}</title>

        @isset($og)
        <meta name="description" content="{{ $og['description'] }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $og['site'] }}">
        <meta property="og:title" content="{{ $og['title'] }}">
        <meta property="og:description" content="{{ $og['description'] }}">
        <meta property="og:url" content="{{ $og['url'] }}">
        @if($og['image'])
        <meta property="og:image" content="{{ $og['image'] }}">
        @endif
        <meta name="twitter:card" content="{{ $og['image'] ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $og['title'] }}">
        <meta name="twitter:description" content="{{ $og['description'] }}">
        @if($og['image'])
        <meta name="twitter:image" content="{{ $og['image'] }}">
        @endif
        @endisset

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
