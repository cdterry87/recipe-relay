@props(['title'])

{{-- Email clients ignore stylesheets, so styles are inline and the layout is a table. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title }}</title>
</head>
<body style="margin:0; padding:0; background:#faf6ee; color:#23291f; font-family:'Open Sans', -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#faf6ee;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;">
                    <tr>
                        <td style="padding:0 0 24px; font-size:20px; font-weight:800; text-transform:uppercase; color:#23291f;">
                            <span style="display:inline-block; width:14px; height:14px; margin-right:6px; border-radius:50%; background:#1f3d29;"></span>{{ config('app.name') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px; background:#fffdf8; border:1px solid #e6dccb; border-radius:16px;">
                            <h1 style="margin:0 0 16px; font-size:24px; line-height:1.15; font-weight:800; text-transform:uppercase; color:#23291f;">{{ $title }}</h1>
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 0 0; font-size:13px; line-height:1.6; color:#5f6157; text-align:center;">
                            {{ config('app.name') }} is a portfolio application that is not intended for public use.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
