<section class="email-friend" aria-labelledby="email-friend-title">
    <h3 id="email-friend-title" class="email-friend__title">Email a friend</h3>
    @guest
        <p class="muted"><a href="{{ route('login') }}">Log in</a> to send this recipe to a friend's inbox.</p>
    @else
        @if ($blocked)
            <p class="muted">{{ $blocked }}</p>
        @elseif ($sent)
            <p class="muted">Sent. <button type="button" class="link-button" wire:click="again">Send to someone else</button></p>
        @else
            <form class="stack stack--sm" wire:submit="send" novalidate>
                <x-ui.field name="email" type="email" label="Friend's email" autocomplete="off" required wire:model="email" hint="We send it once and don't keep the address." />
                <x-ui.field name="note" as="textarea" label="Add a note" maxlength="300" placeholder="Optional, such as “We made this last week!”" wire:model="note" rows="2" />
                <div>
                    <x-ui.button type="submit" size="sm" icon="paper-airplane" wire:loading.attr="disabled" wire:target="send">Send</x-ui.button>
                </div>
            </form>
        @endif
    @endguest
</section>
