@props(['tone' => null])

<p {{ $attributes->class(['notice', 'notice--'.$tone => filled($tone)]) }}>{{ $slot }}</p>
