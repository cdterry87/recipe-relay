<?php

use App\Rules\MaxMegapixels;
use App\Support\Avatars;
use App\Support\SeedPhotos;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::app', ['width' => 'reading']), Title('Profile settings')] class extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $bio = '';

    public string $timezone = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $photo = null;

    /** For the demo account, which picks a seed photo instead of uploading. */
    public string $seedPhoto = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->bio = (string) $user->bio;
        $this->timezone = $user->timezone;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:280'],
            'timezone' => ['required', 'timezone'],
        ], [
            'timezone.timezone' => 'Pick a timezone from the list, such as America/Chicago.',
        ]);

        auth()->user()->update([
            'name' => trim($validated['name']),
            'bio' => filled($validated['bio']) ? trim($validated['bio']) : null,
            'timezone' => $validated['timezone'],
        ]);

        $this->dispatch('toast', type: 'success', message: 'Your profile has been saved.');
    }

    /**
     * Stores the photo as soon as one is picked.
     */
    public function updatedSeedPhoto(): void
    {
        abort_unless(auth()->user()->is_demo, 403);

        if (! SeedPhotos::exists($this->seedPhoto)) {
            $this->addError('seedPhoto', 'Pick one of the photos shown.');

            return;
        }

        Avatars::useSeedPhoto(auth()->user(), $this->seedPhoto);

        $this->dispatch('toast', type: 'success', message: 'Looking good! Your photo has been updated.');
    }

    public function updatedPhoto(): void
    {
        abort_if(auth()->user()->is_demo, 403);

        $key = 'avatar|'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->reset('photo');
            $this->addError('photo', __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => RateLimiter::availableIn($key)]));

            return;
        }

        RateLimiter::hit($key, 600);

        $this->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120', 'dimensions:min_width=64,min_height=64,max_width=8000,max_height=8000', new MaxMegapixels(50)],
        ], [
            'photo.dimensions' => 'Choose a photo at least 64 pixels wide and tall.',
        ], ['photo' => 'photo']);

        try {
            Avatars::store(auth()->user(), $this->photo);
        } catch (\Throwable) {
            $this->addError('photo', "That photo couldn't be read. Please try a different one.");

            return;
        } finally {
            $this->photo?->delete();
            $this->reset('photo');
        }

        $this->dispatch('toast', type: 'success', message: 'Looking good! Your photo has been updated.');
    }

    public function removePhoto(): void
    {
        Avatars::remove(auth()->user());

        $this->dispatch('toast', type: 'success', message: 'Your photo has been removed.');
    }
};
?>

<div class="settings">
    <header class="stack stack--sm">
        <p class="eyebrow">Settings</p>
        <h1>Your <em>profile</em></h1>
        <x-settings.nav />
    </header>

    <x-ui.card title="Photo">
        <div class="avatar-editor">
            <x-ui.avatar :user="auth()->user()" size="lg" />
            @if (auth()->user()->is_demo)
                <div class="stack stack--sm">
                    <x-demo.photo-picker name="seedPhoto" :selected="str_replace('seed/', '', (string) auth()->user()->avatar_path)" label="Choose a new photo" wire:model.live="seedPhoto" />
                    @error('seedPhoto')<p class="field__error" role="alert">{{ $message }}</p>@enderror
                    @if (auth()->user()->avatar_path)
                        <div>
                            <x-ui.button variant="ghost" size="sm" icon="trash" wire:click="removePhoto">Remove photo</x-ui.button>
                        </div>
                    @endif
                </div>
            @else
                <div class="stack stack--sm">
                    <label class="field__label" for="photo">Choose a new photo</label>
                    <input class="avatar-editor__file" id="photo" type="file" accept="image/jpeg,image/png,image/webp,image/gif" wire:model="photo" aria-describedby="photo-hint{{ $errors->has('photo') ? ' photo-error' : '' }}">
                    <p class="field__hint" id="photo-hint">JPG, PNG, WebP or GIF, up to 5 MB. We crop it to a square.</p>
                    <p class="field__hint" wire:loading wire:target="photo">Uploading…</p>
                    @error('photo')<p class="field__error" id="photo-error" role="alert">{{ $message }}</p>@enderror
                    @if (auth()->user()->avatar_path)
                        <div>
                            <x-ui.button variant="ghost" size="sm" icon="trash" wire:click="removePhoto">Remove photo</x-ui.button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </x-ui.card>

    <x-ui.card title="About you">
        <form class="stack" wire:submit="save" novalidate>
            <x-ui.field name="name" label="Name" autocomplete="name" hint="Shown on your recipes and profile." required wire:model="name" />
            <div class="stack stack--sm" x-data>
                <x-ui.field name="bio" label="Bio" as="textarea" maxlength="280" placeholder="What do you love to cook?" hint="Up to 280 characters." wire:model="bio" />
                <p class="counter" aria-hidden="true" x-text="`${($wire.bio ?? '').length} / 280`"></p>
            </div>
            <x-ui.field name="timezone" label="Timezone" list="timezones" autocomplete="off" spellcheck="false" hint="Used for your weekly digest. Start typing a city, such as Chicago." required wire:model="timezone" />
            <datalist id="timezones">
                @foreach (timezone_identifiers_list() as $zone)
                    <option value="{{ $zone }}"></option>
                @endforeach
            </datalist>
            <div class="settings__actions">
                <x-ui.button type="submit" wire:loading.attr="disabled">Save profile</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
