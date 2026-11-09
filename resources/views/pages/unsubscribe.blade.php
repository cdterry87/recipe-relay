<x-layouts::app title="Unsubscribe" width="narrow">
    <x-slot:head><meta name="robots" content="noindex, nofollow"></x-slot:head>

    <section class="message-page">
        <span class="message-page__art" aria-hidden="true">{{ $done ? '👋' : '✉️' }}</span>
        @if ($done)
            <h1>You're <em>unsubscribed</em></h1>
            <p class="muted">We won't send you {{ str($preference->label())->lower() }} emails any more. You can turn them back on in your settings at any time.</p>
            <x-ui.button variant="secondary" :href="route('settings.notifications')">Email settings</x-ui.button>
        @else
            <h1>Unsubscribe from <em>{{ str($preference->label())->lower() }}</em> emails?</h1>
            <p class="muted">{{ $preference->description() }}</p>
            <form method="POST" action="{{ $action }}">
                <x-ui.button type="submit">Unsubscribe</x-ui.button>
            </form>
        @endif
    </section>
</x-layouts::app>
