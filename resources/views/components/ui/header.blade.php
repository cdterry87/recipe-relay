<header class="site-header">
    <div class="site-header__inner">
        <x-ui.logo />
        <div class="site-header__actions">
            {{ $slot }}
            <x-ui.theme-toggle />
        </div>
    </div>
</header>
