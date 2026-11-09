@props(['title' => null, 'description' => null, 'width' => 'page'])

{{-- width: "page" (default), "reading" for long text, or "narrow" for small forms. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head', ['title' => $title, 'description' => $description, 'head' => $head ?? null])
    </head>
    <body class="app">
        <a class="skip-link" href="#main">Skip to content</a>
        <x-ui.header />
        <x-ui.verify-banner />
        <main id="main" @class(['app__main', "app__main--{$width}" => $width !== 'page'])>{{ $slot }}</main>
        <x-ui.footer />
        <x-ui.toasts />
        @livewireScripts
    </body>
</html>
