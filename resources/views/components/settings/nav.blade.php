<nav class="settings__nav" aria-label="Settings">
    @foreach (['settings.profile' => 'Profile', 'settings.account' => 'Account', 'settings.notifications' => 'Email'] as $route => $label)
        <a class="settings__tab" href="{{ route($route) }}" wire:navigate @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
