<x-mail.layout title="Reset your password">
    <p style="margin:0 0 16px; font-size:16px; line-height:1.6;">Hi {{ $name }},</p>
    <p style="margin:0 0 24px; font-size:16px; line-height:1.6;">We received a request to reset the password for your {{ config('app.name') }} account. Choose a new one with the button below.</p>
    <x-mail.button :url="$url">Reset password</x-mail.button>
    <p style="margin:24px 0 0; font-size:14px; line-height:1.6; color:#5c5c5c;">This link expires in {{ $expires }} minutes. If you didn't ask to reset your password, you can ignore this email.</p>
    <p style="margin:16px 0 0; font-size:14px; line-height:1.6; color:#5c5c5c;">If the button doesn't work, paste this link into your browser:<br><a href="{{ $url }}" style="color:#c7352b; word-break:break-all;">{{ $url }}</a></p>
</x-mail.layout>
