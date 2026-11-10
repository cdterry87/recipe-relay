<x-mail.layout title="You have a new follower">
    <p style="margin:0 0 16px; font-size:16px; line-height:1.6;">Hi {{ $name }},</p>
    <p style="margin:0 0 24px; font-size:16px; line-height:1.6;"><strong>{{ $actor['name'] }}</strong> started following you on {{ config('app.name') }}. Your new public recipes will show up in their feed and weekly digest.</p>
    <x-mail.button :url="route('creators.show', $actor['uuid'])">See their profile</x-mail.button>

    <x-slot:footer>
        You asked to hear about new followers.
        <a href="{{ $settingsUrl }}" style="color:#5f6157;">Email settings</a> ·
        <a href="{{ $unsubscribeUrl }}" style="color:#5f6157;">Unsubscribe</a>
    </x-slot:footer>
</x-mail.layout>
