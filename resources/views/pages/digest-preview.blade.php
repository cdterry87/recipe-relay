<x-layouts::app title="Digest preview" width="reading">
    <div class="stack stack--lg">
        <header class="section__header">
            <p class="eyebrow">Weekly digest</p>
            <h1>Your next <em>digest</em></h1>
            <p class="muted">
                @if (! $user->canReceiveEmail())
                    {{ $user->is_demo ? 'The demo account never receives email, but this is what the digest looks like.' : 'Confirm your email address to receive the digest.' }}
                @elseif (! $user->prefers(App\Enums\EmailPreference::Digest))
                    The digest is turned off for you.
                @elseif ($digest->isEmpty())
                    Nobody you follow has shared anything new yet, so it won't be sent unless they do.
                @else
                    It goes out {{ $next->format('l, F j \a\t g A') }} ({{ $user->timezone }}).
                @endif
                <a href="{{ route('settings.notifications') }}" wire:navigate>Change email settings</a>
            </p>
        </header>

        <section class="digest-frame" aria-label="Preview of your weekly digest email">{!! $email !!}</section>
    </div>
</x-layouts::app>
