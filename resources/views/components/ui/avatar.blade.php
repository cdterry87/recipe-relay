@props(['user', 'size' => 'md'])

{{-- A profile picture, or the person's initials on a colour picked from their UUID. --}}
@if ($url = $user->avatarUrl())
    <img {{ $attributes->class(['avatar', "avatar--{$size}"]) }} src="{{ $url }}" alt="" width="400" height="400" loading="lazy" decoding="async">
@else
    <span {{ $attributes->class(['avatar', "avatar--{$size}", 'avatar--initials', 'avatar--tone-'.(hexdec(substr(md5((string) $user->uuid), 0, 2)) % 4)]) }} aria-hidden="true">{{ $user->initials() }}</span>
@endif
