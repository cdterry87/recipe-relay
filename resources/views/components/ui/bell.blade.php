@php($unread = auth()->user()->unreadNotifications()->count())

{{-- The notifications link, with a count of unread ones. --}}
<a class="bell" href="{{ route('my.notifications') }}" wire:navigate @if (request()->routeIs('my.notifications')) aria-current="page" @endif>
    <x-ui.icon name="bell" />
    <span class="visually-hidden">Notifications{{ $unread ? ", {$unread} unread" : '' }}</span>
    @if ($unread)<span class="bell__count" aria-hidden="true">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
</a>
