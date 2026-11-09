<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function account(User $user)
{
    return Livewire::actingAs($user)->test('pages::settings.account');
}

it('shows the account settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/settings/account')->assertOk()->assertSee($user->email)->assertSee('Delete account');
});

it('changes the email and asks to confirm the new one', function () {
    Notification::fake();
    $user = User::factory()->create(['password' => 'secret-password']);

    account($user)
        ->set('email', 'new@example.com')
        ->set('email_password', 'secret-password')
        ->call('updateEmail')
        ->assertHasNoErrors()
        ->assertSet('email_password', '')
        ->assertDispatched('toast', type: 'success');

    $user->refresh();

    expect($user->email)->toBe('new@example.com')
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('does nothing when the email is unchanged', function () {
    Notification::fake();
    $user = User::factory()->create();

    account($user)->set('email', strtoupper($user->email))->call('updateEmail')->assertHasNoErrors()->assertNotDispatched('toast');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Notification::assertNothingSent();
});

it('requires the current password to change the email', function (string $password) {
    $user = User::factory()->create(['password' => 'secret-password']);

    account($user)
        ->set('email', 'new@example.com')
        ->set('email_password', $password)
        ->call('updateEmail')
        ->assertHasErrors('email_password');

    expect($user->fresh()->email)->not->toBe('new@example.com');
})->with(['', 'wrong-password']);

it('lets a Google-only member change their email without a password', function () {
    Notification::fake();
    $user = User::factory()->google()->create();

    account($user)->set('email', 'new@example.com')->call('updateEmail')->assertHasNoErrors();

    expect($user->fresh()->email)->toBe('new@example.com');
});

it('rejects a taken or reserved email', function (string $email) {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create(['password' => 'secret-password']);

    account($user)
        ->set('email', $email)
        ->set('email_password', 'secret-password')
        ->call('updateEmail')
        ->assertHasErrors('email');
})->with(['taken@example.com', 'demo@example.test', 'not-an-email']);

it('changes the password and keeps this session', function () {
    $user = User::factory()->create(['password' => 'secret-password', 'remember_token' => 'old']);

    account($user)
        ->set('current_password', 'secret-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertSet('password', '')
        ->assertDispatched('toast', type: 'success');

    $user->refresh();

    expect(Hash::check('new-password', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe('old');

    $this->assertAuthenticatedAs($user);
});

it('requires the current password to change the password', function () {
    $user = User::factory()->create(['password' => 'secret-password']);

    account($user)
        ->set(['current_password' => 'wrong', 'password' => 'new-password', 'password_confirmation' => 'new-password'])
        ->call('updatePassword')
        ->assertHasErrors('current_password')
        ->assertSet('current_password', '');

    expect(Hash::check('secret-password', $user->fresh()->password))->toBeTrue();
});

it('validates the new password', function (array $input) {
    $user = User::factory()->create(['password' => 'secret-password']);

    account($user)
        ->set(['current_password' => 'secret-password', 'password' => 'new-password', 'password_confirmation' => 'new-password', ...$input])
        ->call('updatePassword')
        ->assertHasErrors('password');
})->with([
    'short' => [['password' => 'short', 'password_confirmation' => 'short']],
    'mismatched' => [['password_confirmation' => 'different']],
]);

it('lets a Google-only member set a first password', function () {
    $user = User::factory()->google()->create();

    $this->actingAs($user)->get('/settings/account')->assertSee('Set a password');

    account($user)
        ->set(['password' => 'new-password', 'password_confirmation' => 'new-password'])
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('limits wrong password answers across the page', function () {
    $user = User::factory()->create(['password' => 'secret-password']);
    $component = account($user);

    foreach (range(1, 5) as $attempt) {
        $component->set(['current_password' => 'wrong', 'password' => 'new-password', 'password_confirmation' => 'new-password'])->call('updatePassword');
    }

    $component->set('delete_confirmation', 'secret-password')
        ->call('deleteAccount')
        ->assertHasErrors('delete_confirmation');

    expect(User::find($user->id))->not->toBeNull();
});

it('deletes the account and its photo after confirming the password', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/me.webp', 'image');
    $user = User::factory()->create(['password' => 'secret-password']);
    $user->forceFill(['avatar_path' => 'avatars/me.webp'])->save();

    account($user)
        ->set('delete_confirmation', 'secret-password')
        ->call('deleteAccount')
        ->assertRedirect(route('home'));

    expect(User::find($user->id))->toBeNull();
    Storage::disk('public')->assertMissing('avatars/me.webp');
    $this->assertGuest();
});

it('keeps the account after a wrong password', function () {
    $user = User::factory()->create(['password' => 'secret-password']);

    account($user)->set('delete_confirmation', 'wrong')->call('deleteAccount')->assertHasErrors('delete_confirmation');

    expect(User::find($user->id))->not->toBeNull();
});

it('asks a Google-only member to type their email to delete', function () {
    $user = User::factory()->google()->create(['email' => 'julia@example.com']);

    account($user)->set('delete_confirmation', 'someone@example.com')->call('deleteAccount')->assertHasErrors('delete_confirmation');
    account($user)->set('delete_confirmation', '')->call('deleteAccount')->assertHasErrors('delete_confirmation');

    account($user)->set('delete_confirmation', 'Julia@Example.com')->call('deleteAccount')->assertRedirect(route('home'));

    expect(User::find($user->id))->toBeNull();
});
