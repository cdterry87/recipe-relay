<footer class="site-footer">
    <div class="site-footer__inner">
        <div class="site-footer__brand">
            <x-ui.logo />
            <p>A friendly place to keep your favorite recipes, follow cooks you love, and pass the good ones along.</p>
        </div>

        <nav aria-labelledby="footer-explore">
            <h2 class="site-footer__heading" id="footer-explore">Explore</h2>
            <ul class="site-footer__links">
                <li><a href="{{ route('discover') }}">Discover recipes</a></li>
                <li><a href="{{ route('creators') }}">Trending creators</a></li>
                <li><a href="{{ route('home') }}">Home</a></li>
            </ul>
        </nav>

        <nav aria-labelledby="footer-kitchen">
            <h2 class="site-footer__heading" id="footer-kitchen">Your kitchen</h2>
            <ul class="site-footer__links">
                @auth
                    <li><a href="{{ route('my.recipes') }}">My recipes</a></li>
                    <li><a href="{{ route('settings.profile') }}">Profile settings</a></li>
                    <li><a href="{{ route('settings.account') }}">Account settings</a></li>
                @else
                    <li><a href="{{ route('login') }}">Log in</a></li>
                    @if (App\Support\Registration::open())
                        <li><a href="{{ route('register') }}">Create an account</a></li>
                    @endif
                @endauth
            </ul>
        </nav>

        <nav aria-labelledby="footer-about">
            <h2 class="site-footer__heading" id="footer-about">About</h2>
            <ul class="site-footer__links">
                <li><a href="https://chaseterry.com" rel="noopener" target="_blank">Meet the developer<span class="visually-hidden"> (opens in a new tab)</span></a></li>
                <li><a href="https://github.com/cdterry87/recipe-relay" rel="noopener" target="_blank">Source code<span class="visually-hidden"> (opens in a new tab)</span></a></li>
            </ul>
        </nav>
    </div>

    <div class="site-footer__legal">
        <div class="site-footer__credits">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
            <span>Developed by <a href="https://chaseterry.com" rel="noopener" target="_blank">Chase Terry<span class="visually-hidden"> (opens in a new tab)</span></a></span>
        </div>
        <a href="{{ route('privacy-policy') }}">Privacy Policy</a>
    </div>
</footer>
