<a {{ $attributes->class('logo')->merge(['href' => url('/')]) }}>
    {{-- A wooden spoon passed like a relay baton. --}}
    <svg class="logo__mark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" aria-hidden="true">
        <circle class="logo__badge" cx="20" cy="20" r="19" />
        <g transform="rotate(45 20 20)">
            <ellipse class="logo__spoon" cx="20" cy="11" rx="5" ry="6.5" />
            <rect class="logo__spoon" x="18.6" y="16" width="2.8" height="18" rx="1.4" />
        </g>
        <path class="logo__swoosh" d="M6.5 25.5l4.5-4.5M9.5 31l4-4" />
    </svg>
    <span class="logo__text">{{ config('app.name') }}</span>
</a>
