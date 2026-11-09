{{-- Reminds a signed-in member to confirm their email, which they need before sharing recipes. --}}
@auth
    @unless (auth()->user()->hasVerifiedEmail() || request()->routeIs('verification.notice'))
        <div class="banner" role="region" aria-label="Email confirmation">
            <p>Please confirm your email address so you can share your recipes. <a href="{{ route('verification.notice') }}" wire:navigate>Resend the link</a></p>
        </div>
    @endunless
@endauth
