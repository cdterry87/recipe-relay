@props(['title', 'preview' => false])

{{-- Email clients ignore stylesheets, so styles are inline and the layout is a table. --}}
@if ($preview)
<div class="email-preview" style="background:#faf6ee; color:#23291f; font-family:'Open Sans', -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif;">
    @include('mail.partials.body', ['heading' => 'h2'])
</div>
@else
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title }}</title>
</head>
<body style="margin:0; padding:0; background:#faf6ee; color:#23291f; font-family:'Open Sans', -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif;">
    @include('mail.partials.body', ['heading' => 'h1'])
</body>
</html>

@endif
