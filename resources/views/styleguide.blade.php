{{-- Local and testing only: a quick visual check of the design system. --}}
<x-layouts::app title="Style guide">
    <div class="stack stack--lg">
        <header class="stack stack--sm">
            <h1>Style guide</h1>
            <p class="muted">Shared tokens and components. Switch the theme in the header to check both.</p>
        </header>

        <x-ui.card title="Colour">
            <div class="styleguide__swatches">
                @foreach (['bg', 'surface', 'surface-hover', 'border', 'text', 'text-muted', 'brand', 'primary', 'primary-subtle', 'secondary', 'secondary-subtle', 'accent', 'accent-subtle', 'negative', 'negative-subtle'] as $token)
                    <span class="styleguide__swatch" style="--swatch: var(--color-{{ $token }})">{{ $token }}</span>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card title="Type">
            <div class="stack">
                <h1>Heading one</h1>
                <h2>Heading two</h2>
                <h3>Heading three</h3>
                <p>Body text is set in Atkinson Hyperlegible Next at 18px, so recipes stay easy to read for everyone. <a href="#">A link looks like this.</a></p>
                <p class="muted">Muted text for supporting details.</p>
            </div>
        </x-ui.card>

        <x-ui.card title="Buttons">
            <div class="cluster">
                <x-ui.button>Primary</x-ui.button>
                <x-ui.button variant="secondary">Secondary</x-ui.button>
                <x-ui.button variant="accent">Accent</x-ui.button>
                <x-ui.button variant="ghost">Ghost</x-ui.button>
                <x-ui.button size="sm">Small</x-ui.button>
                <x-ui.button variant="secondary" icon="plus" label="Add" />
                <x-ui.button disabled>Disabled</x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card title="Chips and notices">
            <div class="stack">
                <div class="cluster">
                    <x-ui.chip>Default</x-ui.chip>
                    <x-ui.chip tone="primary">Dinner</x-ui.chip>
                    <x-ui.chip tone="secondary">Vegetarian</x-ui.chip>
                    <x-ui.chip tone="accent">Quick &amp; Easy</x-ui.chip>
                </div>
                <x-ui.notice>Yum! Saved to your cookbook.</x-ui.notice>
                <x-ui.notice tone="info">The demo data resets every night.</x-ui.notice>
                <x-ui.notice tone="error">Something went wrong. Please try again.</x-ui.notice>
            </div>
        </x-ui.card>
    </div>
</x-layouts::app>
