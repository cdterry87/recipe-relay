<?php

use App\Enums\EmailPreference;
use App\Models\User;
use App\Support\Unsubscribe;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

it('shows and saves email settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('settings.notifications'))->assertOk()->assertSee('Weekly digest');

    Livewire::actingAs($user)->test('pages::settings.notifications')
        ->assertSet('emails.digest', true)
        ->assertSet('emails.new-follower', false)
        ->set('emails.digest', false)
        ->set('emails.new-rating', true)
        ->set('digest_day', 5)
        ->set('digest_hour', 18)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $user->refresh();
    expect($user->prefers(EmailPreference::Digest))->toBeFalse()
        ->and($user->prefers(EmailPreference::NewRating))->toBeTrue()
        ->and($user->prefers(EmailPreference::NewFollower))->toBeFalse()
        ->and($user->digest_day)->toBe(5)
        ->and($user->digest_hour)->toBe(18);
});

it('rejects impossible digest times', function () {
    Livewire::actingAs(User::factory()->create())->test('pages::settings.notifications')
        ->set('digest_day', 7)
        ->set('digest_hour', 24)
        ->call('save')
        ->assertHasErrors(['digest_day', 'digest_hour']);
});

it('explains that the demo account gets no email', function () {
    $this->actingAs(User::factory()->demo()->create())->get(route('settings.notifications'))->assertSee('never receives email');
});

it('asks before unsubscribing, then turns that email off', function () {
    $user = User::factory()->create();
    $url = Unsubscribe::url($user, EmailPreference::Digest);

    $this->get($url)->assertOk()->assertSee('Unsubscribe from <em>weekly digest</em> emails?', false);
    expect($user->fresh()->prefers(EmailPreference::Digest))->toBeTrue();

    $this->post($url)->assertOk()->assertSee('unsubscribed');
    expect($user->fresh()->prefers(EmailPreference::Digest))->toBeFalse();
});

it('supports one-click unsubscribe from mail apps', function () {
    $user = User::factory()->create(['email_preferences' => ['new-follower' => true]]);

    $this->post(Unsubscribe::url($user, EmailPreference::NewFollower), ['List-Unsubscribe' => 'One-Click'])->assertNoContent();

    expect($user->fresh()->prefers(EmailPreference::NewFollower))->toBeFalse()
        ->and($user->fresh()->prefers(EmailPreference::Digest))->toBeTrue();
});

it('rejects tampered or unknown unsubscribe links', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $url = Unsubscribe::url($user, EmailPreference::Digest);

    $this->get(str_replace($user->uuid, $other->uuid, $url))->assertForbidden();
    $this->post(str_replace('/digest', '/new-rating', $url))->assertForbidden();
    $this->get(route('unsubscribe', ['user' => $user->uuid, 'type' => 'digest']))->assertForbidden();
    $this->get(URL::signedRoute('unsubscribe', ['user' => $user->uuid, 'type' => 'spam']))->assertNotFound();

    expect($user->fresh()->prefers(EmailPreference::Digest))->toBeTrue();
});
