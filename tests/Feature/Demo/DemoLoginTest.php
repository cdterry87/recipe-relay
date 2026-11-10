<?php

use App\Support\DemoWorld;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config(['relay.demo_mode' => true]);
    DemoWorld::reset();
});

it('offers the demo login only in demo mode', function () {
    $this->get(route('login'))->assertSee('Log in as the demo user')->assertDontSee('demo-password');

    config(['relay.demo_mode' => false]);

    $this->get(route('login'))->assertDontSee('Log in as the demo user');
    Livewire::test('pages::auth.login')->call('loginAsDemo')->assertNotFound();
});

it('logs in as the demo user after the security check', function () {
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

    Livewire::test('pages::auth.login')
        ->set('turnstile', 'token')
        ->call('loginAsDemo')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'))
        ->assertDispatched('turnstile-reset');

    expect(auth()->user()?->is_demo)->toBeTrue();
});

it('refuses the demo login when the security check fails', function () {
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

    Livewire::test('pages::auth.login')
        ->set('turnstile', 'token')
        ->call('loginAsDemo')
        ->assertHasErrors('turnstile');

    Livewire::test('pages::auth.login')->call('loginAsDemo')->assertHasErrors('turnstile');

    $this->assertGuest();
});

it('limits demo logins per address', function () {
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);
    $component = Livewire::test('pages::auth.login');

    foreach (range(1, 10) as $i) {
        $component->set('turnstile', 'token')->call('loginAsDemo');
    }

    $component->set('turnstile', 'token')->call('loginAsDemo')->assertHasErrors('turnstile');
    Http::assertSentCount(10);
});

it('offers the demo on the home page in demo mode', function () {
    $this->get(route('home'))->assertSee('Try the demo');
});
