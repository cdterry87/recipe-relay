<x-mail.layout title="Your recipe got a rating">
    <p style="margin:0 0 16px; font-size:16px; line-height:1.6;">Hi {{ $name }},</p>
    <p style="margin:0 0 24px; font-size:16px; line-height:1.6;"><strong>{{ $actor['name'] }}</strong> saved <strong>{{ $recipe['title'] }}</strong> to their cookbook and gave it <span style="color:#9a5b1f;">{{ str_repeat('★', $stars) }}{{ str_repeat('☆', 5 - $stars) }}</span> ({{ $stars }} out of 5).</p>
    <x-mail.button :url="route('recipes.show', $recipe['uuid'])">View your recipe</x-mail.button>

    <x-slot:footer>
        You asked to hear about new ratings.
        <a href="{{ $settingsUrl }}" style="color:#5f6157;">Email settings</a> ·
        <a href="{{ $unsubscribeUrl }}" style="color:#5f6157;">Unsubscribe</a>
    </x-slot:footer>
</x-mail.layout>
