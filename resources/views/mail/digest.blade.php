<x-mail.layout title="Your weekly digest" :preview="$preview">
    <p style="margin:0 0 16px; font-size:16px; line-height:1.6;">Hi {{ $name }},</p>

    @if ($digest->isEmpty())
        <p style="margin:0 0 24px; font-size:16px; line-height:1.6;">Nobody you follow has shared a new recipe since your last digest, so this week's email would be skipped. Here's what's popular instead.</p>
    @else
        <p style="margin:0 0 24px; font-size:16px; line-height:1.6;">Here's what the cooks you follow have been making this week.</p>

        @foreach ($digest->creators as $entry)
            <h2 style="margin:24px 0 12px; font-size:15px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; color:#2f5d3a;">
                <a href="{{ route('creators.show', $entry['creator']) }}" style="color:#2f5d3a; text-decoration:none;">{{ $entry['creator']->name }}</a>
            </h2>
            @foreach ($entry['recipes'] as $recipe)
                <x-mail.recipe :recipe="$recipe" />
            @endforeach
            @if ($entry['more'] > 0)
                <p style="margin:0 0 12px; font-size:14px;"><a href="{{ route('creators.show', $entry['creator']) }}" style="color:#2f5d3a;">and {{ $entry['more'] }} more from {{ $entry['creator']->name }}</a></p>
            @endif
        @endforeach
    @endif

    @if ($digest->trending->isNotEmpty())
        <h2 style="margin:32px 0 12px; padding-top:24px; border-top:1px solid #e6dccb; font-size:15px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; color:#9a5b1f;">Trending this week</h2>
        @foreach ($digest->trending as $recipe)
            <x-mail.recipe :recipe="$recipe" />
        @endforeach
    @endif

    <div style="margin-top:24px;">
        <x-mail.button :url="route('my.following')">See your feed</x-mail.button>
    </div>

    <x-slot:footer>
        You get this email once a week because you follow cooks on {{ config('app.name') }}.
        <a href="{{ $settingsUrl }}" style="color:#5f6157;">Change when it arrives</a> or
        <a href="{{ $unsubscribeUrl }}" style="color:#5f6157;">unsubscribe</a>.
    </x-slot:footer>
</x-mail.layout>
