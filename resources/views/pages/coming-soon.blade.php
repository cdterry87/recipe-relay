{{-- Stands in for pages that arrive in a later milestone. --}}
<x-layouts::app :title="$title">
    <section class="coming-soon">
        <span class="coming-soon__art" aria-hidden="true">{{ $emoji }}</span>
        <p class="eyebrow">{{ $eyebrow }}</p>
        <h1>{!! $heading !!}</h1>
        <p>{{ $message }}</p>
        <x-ui.button :href="route('home')">Back to {{ config('app.name') }}</x-ui.button>
    </section>
</x-layouts::app>
