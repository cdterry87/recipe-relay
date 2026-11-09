@php($user = auth()->user())

{{-- A disclosure menu: a button that shows a short list of links. Escape or a click outside closes it. --}}
<div class="user-menu" x-data="{ open: false }" x-on:keydown.escape.window="if (open) { open = false; $refs.button.focus() }" x-on:click.outside="open = false">
    <button class="user-menu__button" type="button" x-ref="button" x-on:click="open = ! open" x-bind:aria-expanded="open" aria-controls="user-menu-list">
        <x-ui.avatar :user="$user" size="sm" />
        <span class="visually-hidden">Account menu for {{ $user->name }}</span>
        <x-ui.icon name="chevron-down" size="sm" />
    </button>
    <div class="user-menu__panel" id="user-menu-list" x-show="open" x-cloak x-transition.opacity.duration.150ms>
        <p class="user-menu__name">{{ $user->name }}</p>
        <ul class="user-menu__list">
            <li><a class="user-menu__link" href="{{ route('settings.profile') }}" wire:navigate><x-ui.icon name="user" size="sm" /> Profile</a></li>
            <li><a class="user-menu__link" href="{{ route('settings.account') }}" wire:navigate><x-ui.icon name="pencil" size="sm" /> Account</a></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="user-menu__link" type="submit"><x-ui.icon name="logout" size="sm" /> Log out</button>
                </form>
            </li>
        </ul>
    </div>
</div>
