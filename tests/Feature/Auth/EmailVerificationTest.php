<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

function verificationUrl(User $user, ?string $email = null): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'user' => $user->uuid,
        'hash' => sha1($email ?? $user->email),
    ]);
}

it('builds the link from the uuid, never the id', function () {
    $user = User::factory()->unverified()->create();

    $notification = new VerifyEmailNotification;
    $mail = $notification->toMail($user);
    $url = $mail->viewData['url'];

    expect($url)->toContain('/verify-email/'.$user->uuid.'/')
        ->and($url)->toContain('signature=')
        ->and(view($mail->view[0], $mail->viewData)->render())->toContain('Confirm my email')
        // Plain text must keep the link exactly, with no HTML escaping of "&".
        ->and(view($mail->view[1], $mail->viewData)->render())->toContain($url);
});

it('confirms the email from a signed link', function () {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(verificationUrl($user))
        ->assertRedirect(route('home'))
        ->assertSessionHas('toast.type', 'success');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

it('rejects a link for someone else', function () {
    $user = User::factory()->unverified()->create();
    $other = User::factory()->unverified()->create();

    $this->actingAs($other)->get(verificationUrl($user))->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects a link for an old email address', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(verificationUrl($user, 'old@example.com'))->assertForbidden();
});

it('rejects an unsigned or expired link', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/verify-email/'.$user->uuid.'/'.sha1($user->email))->assertForbidden();

    $url = verificationUrl($user);
    $this->travel(2)->hours();
    $this->actingAs($user)->get($url)->assertForbidden();
});

it('asks guests to log in first', function () {
    $user = User::factory()->unverified()->create();

    $this->get(verificationUrl($user))->assertRedirect(route('login'));
});

it('shows a reminder banner to unconfirmed members', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get('/')
        ->assertSee('Please confirm your email address');

    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertDontSee('Please confirm your email address');
});

it('resends the link, a few times at most', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    $this->get('/verify-email')->assertOk()->assertSee($user->email);

    foreach (range(1, 3) as $attempt) {
        Livewire::test('pages::auth.verify-email')->call('resend')->assertDispatched('toast', type: 'success');
    }

    Livewire::test('pages::auth.verify-email')->call('resend')->assertDispatched('toast', type: 'error');

    Notification::assertSentToTimes($user, VerifyEmailNotification::class, 3);
});

it('sends confirmed members home from the reminder page', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::auth.verify-email')->assertRedirect(route('home'));
});

it('sends nothing once the email is confirmed', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $component = Livewire::actingAs($user)->test('pages::auth.verify-email');

    $user->markEmailAsVerified();

    $component->call('resend')->assertRedirect(route('home'));
    Notification::assertNothingSent();
});
