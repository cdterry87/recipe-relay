<x-mail.layout title="A recipe for you">
    <p style="margin:0 0 16px; font-size:16px; line-height:1.6;"><strong>{{ $sender }}</strong> thought you'd like this recipe.</p>
    @if ($note)
        <p style="margin:0 0 24px; padding:12px 16px; border-left:3px solid #d9c3a0; background:#faf6ee; font-size:15px; line-height:1.6; white-space:pre-line;">{{ $note }}</p>
    @endif
    @if ($image = $recipe->imageUrl(800))
        <a href="{{ $url }}"><img src="{{ $image }}" width="456" alt="" style="display:block; width:100%; max-width:456px; height:auto; margin:0 0 16px; border-radius:12px; border:0;"></a>
    @endif
    <h2 style="margin:0 0 8px; font-size:20px; line-height:1.2; font-weight:800; color:#23291f;">{{ $recipe->title }}</h2>
    @if ($recipe->summary)<p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#5f6157;">{{ $recipe->summary }}</p>@endif
    <p style="margin:0 0 24px; font-size:14px; color:#5f6157;">by {{ $recipe->user->name }}@if ($minutes = App\Models\Recipe::formatMinutes($recipe->totalMinutes())) · {{ $minutes }}@endif</p>
    <x-mail.button :url="$url">Open the recipe</x-mail.button>

    <x-slot:footer>
        {{ $sender }} sent this from {{ config('app.name') }}. We don't keep your address or add you to any list.
    </x-slot:footer>
</x-mail.layout>
