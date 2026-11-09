@php
    $creator = $this->creator;
    $self = auth()->check() && auth()->user()->is($creator);
@endphp

<div class="follow-button">
    @if (! $self && ($this->following || $creator->isFollowable()))
        <x-ui.button
            :variant="$this->following ? 'secondary' : 'primary'"
            :size="$size === 'sm' ? 'sm' : null"
            :icon="$this->following ? 'check' : 'user-plus'"
            wire:click="toggle"
            wire:loading.attr="disabled"
            :aria-pressed="$this->following ? 'true' : 'false'"
        >{{ $this->following ? 'Following' : 'Follow' }}<span class="visually-hidden"> {{ $creator->name }}</span></x-ui.button>
    @endif
</div>
