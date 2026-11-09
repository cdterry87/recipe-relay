Reset your {{ config('app.name') }} password

Hi {{ $name }},

We received a request to reset the password for your {{ config('app.name') }} account. Choose a new one here:

{!! $url !!}

This link expires in {{ $expires }} minutes. If you didn't ask to reset your password, you can ignore this email.
