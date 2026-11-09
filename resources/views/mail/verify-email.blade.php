<x-mail.layout title="Confirm your email">
    <p style="margin:0 0 16px; font-size:16px; line-height:1.6;">Hi {{ $name }},</p>
    <p style="margin:0 0 24px; font-size:16px; line-height:1.6;">Welcome to {{ config('app.name') }}! Confirm your email address so you can share your recipes with everyone.</p>
    <x-mail.button :url="$url">Confirm my email</x-mail.button>
    <p style="margin:24px 0 0; font-size:14px; line-height:1.6; color:#6b5f55;">This link expires in {{ $expires }} minutes. If you didn't create an account, you can ignore this email.</p>
    <p style="margin:16px 0 0; font-size:14px; line-height:1.6; color:#6b5f55;">If the button doesn't work, paste this link into your browser:<br><a href="{{ $url }}" style="color:#a93518; word-break:break-all;">{{ $url }}</a></p>
</x-mail.layout>
