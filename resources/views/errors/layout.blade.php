{{-- Error pages stay simple: no Livewire, no session data, and no database, so they render even when those fail. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head', ['title' => trim($__env->yieldContent('title'))])
    </head>
    <body class="app">
        <a class="skip-link" href="#main">Skip to content</a>
        <header class="site-header">
            <div class="site-header__inner">
                <x-ui.logo />
            </div>
        </header>
        <main id="main" class="app__main">
            <div class="card error-page">
                {{-- An empty cooking pot. --}}
                <svg class="error-page__art" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" aria-hidden="true">
                    <path d="M24 14c0-3 3-3 3-6M32 14c0-3 3-3 3-6M40 14c0-3 3-3 3-6" fill="none" style="stroke: var(--color-border-strong)" stroke-width="2.5" stroke-linecap="round" />
                    <rect x="8" y="22" width="48" height="6" rx="3" style="fill: var(--color-primary)" />
                    <path d="M12 28h40v16a12 12 0 0 1-12 12H24a12 12 0 0 1-12-12Z" style="fill: var(--color-kraft)" />
                    <path d="M4 32h8M52 32h8" style="stroke: var(--color-primary)" stroke-width="4" stroke-linecap="round" />
                    <circle cx="25" cy="40" r="2" style="fill: #2b2622" />
                    <circle cx="39" cy="40" r="2" style="fill: #2b2622" />
                    <path d="M27 48q5-4 10 0" fill="none" style="stroke: #2b2622" stroke-width="2" stroke-linecap="round" />
                </svg>
                <p class="error-page__code">@yield('code')</p>
                <h1>@yield('title')</h1>
                <p class="error-page__message">@yield('message')</p>
                <x-ui.button :href="url('/')">Back to {{ config('app.name') }}</x-ui.button>
                <p class="error-page__note">{{ config('app.name') }} is a portfolio application that is not intended for public use.</p>
            </div>
        </main>
        <x-ui.footer />
    </body>
</html>
