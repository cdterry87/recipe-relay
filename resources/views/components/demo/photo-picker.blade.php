@props(['name', 'selected' => null, 'label' => 'Pick a photo'])

{{-- For the demo account, which can't upload: a grid of the seed food photos to choose from. --}}
<fieldset class="photo-picker">
    <legend class="field__label">{{ $label }}</legend>
    <p class="field__hint">The demo account can't upload files, so choose one of these instead.</p>
    <div class="photo-picker__grid">
        @foreach (App\Support\SeedPhotos::all() as $key => $title)
            <label class="photo-picker__option">
                <input class="visually-hidden" type="radio" name="{{ $name }}" value="{{ $key }}" {{ $attributes->whereStartsWith('wire:') }} @checked($selected === $key)>
                <img src="{{ App\Support\SeedPhotos::url(App\Support\SeedPhotos::path($key), 400) }}" alt="{{ $title }}" width="400" height="300" loading="lazy">
            </label>
        @endforeach
    </div>
</fieldset>
