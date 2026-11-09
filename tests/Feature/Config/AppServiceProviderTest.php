<?php

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;

afterEach(fn () => TrustProxies::flushState());

it('trusts the configured proxies', function () {
    config(['relay.trusted_proxies' => '*']);
    (new AppServiceProvider(app()))->boot();

    $this->get(route('home'), ['X-Forwarded-Proto' => 'https'])
        ->assertHeader('Strict-Transport-Security');
});

it('ignores forwarded headers when no proxy is trusted', function () {
    config(['relay.trusted_proxies' => null]);
    (new AppServiceProvider(app()))->boot();

    $this->get(route('home'), ['X-Forwarded-Proto' => 'https'])
        ->assertHeaderMissing('Strict-Transport-Security');
});

it('builds https links when the app url is https', function () {
    config(['app.url' => 'https://reciperelay.test']);
    (new AppServiceProvider(app()))->boot();

    expect(url('/privacy-policy'))->toStartWith('https://');
});
