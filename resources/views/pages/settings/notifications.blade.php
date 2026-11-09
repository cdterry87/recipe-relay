<?php

use App\Enums\EmailPreference;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Email settings: which optional emails to get, and when the weekly digest arrives.
 */
new #[Layout('layouts::app', ['width' => 'reading']), Title('Email settings')] class extends Component
{
    /** @var array<string, bool> */
    public array $emails = [];

    public int $digest_day = 0;

    public int $digest_hour = 9;

    public function mount(): void
    {
        $user = auth()->user();

        foreach (EmailPreference::cases() as $preference) {
            $this->emails[$preference->value] = $user->prefers($preference);
        }

        $this->digest_day = $user->digest_day;
        $this->digest_hour = $user->digest_hour;
    }

    public function save(): void
    {
        $this->validate([
            'emails' => ['array'],
            'emails.*' => ['boolean'],
            'digest_day' => ['required', 'integer', 'between:0,6'],
            'digest_hour' => ['required', 'integer', 'between:0,23'],
        ]);

        $preferences = collect(EmailPreference::cases())
            ->mapWithKeys(fn (EmailPreference $preference) => [$preference->value => (bool) ($this->emails[$preference->value] ?? false)])
            ->all();

        auth()->user()->update([
            'email_preferences' => $preferences,
            'digest_day' => $this->digest_day,
            'digest_hour' => $this->digest_hour,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Your email settings have been saved.');
    }

    /**
     * @return array<int, string>
     */
    public function days(): array
    {
        return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    }

    /**
     * @return array<int, string>
     */
    public function hours(): array
    {
        return collect(range(0, 23))->mapWithKeys(fn (int $hour) => [$hour => now()->setTime($hour, 0)->format('g:00 A')])->all();
    }
};
?>

@php($user = auth()->user())

<div class="settings">
    <header class="stack stack--sm">
        <p class="eyebrow">Settings</p>
        <h1>Email <em>settings</em></h1>
        <x-settings.nav />
    </header>

    @unless ($user->canReceiveEmail())
        <x-ui.notice tone="info">
            @if ($user->is_demo)
                The demo account never receives email. You can still change these settings and <a href="{{ route('my.digest') }}" wire:navigate>preview the digest</a>.
            @else
                Confirm your email address to start receiving these emails.
            @endif
        </x-ui.notice>
    @endunless

    <form class="stack stack--lg" wire:submit="save" novalidate>
        <x-ui.card title="What we send">
            <div class="stack">
                @foreach (App\Enums\EmailPreference::cases() as $preference)
                    <div class="stack stack--sm">
                        <x-ui.checkbox name="emails.{{ $preference->value }}" :label="$preference->label()" wire:model="emails.{{ $preference->value }}" aria-describedby="email-{{ $preference->value }}-hint" />
                        <p class="field__hint" id="email-{{ $preference->value }}-hint">{{ $preference->description() }}</p>
                    </div>
                @endforeach
                <p class="field__hint">Account emails, such as password resets, are always sent.</p>
            </div>
        </x-ui.card>

        <x-ui.card title="When the digest arrives">
            <div class="stack">
                <div class="settings__row">
                    <x-ui.field name="digest_day" label="Day" as="select" :options="$this->days()" wire:model="digest_day" />
                    <x-ui.field name="digest_hour" label="Time" as="select" :options="$this->hours()" wire:model="digest_hour" />
                </div>
                <p class="field__hint">In your timezone, {{ $user->timezone }}. <a href="{{ route('settings.profile') }}" wire:navigate>Change timezone</a> · <a href="{{ route('my.digest') }}" wire:navigate>Preview your digest</a></p>
            </div>
        </x-ui.card>

        <div class="settings__actions">
            <x-ui.button type="submit" wire:loading.attr="disabled">Save email settings</x-ui.button>
        </div>
    </form>
</div>
