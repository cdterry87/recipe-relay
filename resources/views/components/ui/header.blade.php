<header class="site-header">
    <div class="site-header__inner">
        <x-ui.logo />
        <div class="site-header__actions">
            {{ $slot }}
            <x-ui.theme-toggle />
            @auth
                <x-ui.button size="sm" icon="plus" class="site-header__new" :href="route('recipes.create')" wire:navigate>New recipe</x-ui.button>
                <x-ui.user-menu />
            @else
                @unless (request()->routeIs('login'))
                    <x-ui.button variant="ghost" size="sm" :href="route('login')">Log in</x-ui.button>
                @endunless
                @if (App\Support\Registration::open() && ! request()->routeIs('register'))
                    <x-ui.button size="sm" :href="route('register')">Sign up</x-ui.button>
                @endif
            @endauth
        </div>
    </div>
</header>
