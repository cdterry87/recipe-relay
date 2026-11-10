<nav class="settings__nav" aria-label="Settings">
    {{-- The demo account can't change its email or password, so it has no Account tab. --}}
    @foreach (array_filter(['settings.profile' => 'Profile', 'settings.account' => auth()->user()?->is_demo ? null : 'Account', 'settings.notifications' => 'Email']) as $route => $label)
        <a class="settings__tab" href="{{ route($route) }}" wire:navigate @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
