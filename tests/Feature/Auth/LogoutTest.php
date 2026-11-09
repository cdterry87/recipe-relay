<?php

use App\Models\User;

it('logs the member out and invalidates the session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['marker' => 'kept'])
        ->post('/logout')
        ->assertRedirect(route('home'))
        ->assertSessionMissing('marker');

    $this->assertGuest();
});

it('only accepts logout as a POST from a signed-in member', function () {
    $this->post('/logout')->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get('/logout')->assertMethodNotAllowed();
});

it('shows the account menu to members', function () {
    $user = User::factory()->create(['name' => 'Julia Child']);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('Account menu for Julia Child')
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee(route('settings.profile'))
        ->assertDontSee('Sign up');
});
