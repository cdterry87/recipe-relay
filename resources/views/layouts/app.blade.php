@props(['title' => null, 'description' => null, 'reading' => false])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head', ['title' => $title, 'description' => $description])
    </head>
    <body class="app">
        <a class="skip-link" href="#main">Skip to content</a>
        <x-ui.header />
        <main id="main" @class(['app__main', 'app__main--reading' => $reading])>{{ $slot }}</main>
        <x-ui.footer />
        @livewireScripts
    </body>
</html>
