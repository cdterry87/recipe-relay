<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="color-scheme" content="light dark">
<title>{{ filled($title ?? null) ? $title.' · '.config('app.name') : config('app.name') }}</title>
@if (filled($description ?? null))
    <meta name="description" content="{{ $description }}">
@endif
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="manifest" href="/site.webmanifest">
<meta name="theme-color" content="#2f5d3a">
{{-- Runs before first paint so a saved theme never flashes the wrong colours. --}}
<script>
    try {
        const theme = localStorage.getItem('recipe-relay-theme');
        if (theme === 'light' || theme === 'dark') {
            document.documentElement.dataset.theme = theme;
        }
    } catch {}
</script>
{{ $head ?? '' }}
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
