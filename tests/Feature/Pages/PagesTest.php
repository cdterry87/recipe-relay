<?php

it('shows the home page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Pass along the recipes you', false)
        ->assertSee('<a class="skip-link" href="#main">Skip to content</a>', false);
});

it('shows the privacy policy', function () {
    $this->get(route('privacy-policy'))
        ->assertOk()
        ->assertSee('<h1>Privacy Policy</h1>', false)
        ->assertSee('portfolio application that is not intended for public use')
        ->assertSee('Cloudflare Turnstile');
});

it('shows the footer on every page', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('<footer class="site-footer">', false)
        ->assertSee('&copy; '.date('Y').' '.config('app.name'), false)
        ->assertSee('href="'.route('privacy-policy').'"', false)
        ->assertSee('<a href="https://chaseterry.com" rel="noopener" target="_blank">Chase Terry<span class="visually-hidden"> (opens in a new tab)</span></a>', false);
})->with(['home', 'privacy-policy']);

it('shows the style guide outside production', function () {
    $this->get(route('styleguide'))->assertOk()->assertSee('Style guide');
});

it('hides the style guide in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get(route('styleguide'))->assertNotFound();
});

it('answers the health check', function () {
    $this->get('/up')->assertOk();
});
