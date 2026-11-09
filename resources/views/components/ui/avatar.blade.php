@props(['user' => null, 'name' => null, 'src' => null, 'size' => 'md'])

{{--
    A profile picture, or initials on a tint picked from a stable seed.
    Pass a User, or a plain name (and optional src) for sample content.
--}}
@php
    $name = $user?->name ?? $name;
    $src = $user?->avatarUrl() ?? $src;
    $seed = (string) ($user?->uuid ?? $name);
    $initials = $user?->initials() ?? Illuminate\Support\Str::of($name)->squish()->explode(' ')->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
@endphp

@if ($src)
    <img {{ $attributes->class(['avatar', "avatar--{$size}"]) }} src="{{ $src }}" alt="" width="400" height="400" loading="lazy" decoding="async">
@else
    <span {{ $attributes->class(['avatar', "avatar--{$size}", 'avatar--initials', 'avatar--tone-'.(hexdec(substr(md5($seed), 0, 2)) % 4)]) }} aria-hidden="true">{{ $initials }}</span>
@endif
