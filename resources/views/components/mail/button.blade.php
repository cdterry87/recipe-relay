@props(['url'])

<table role="presentation" cellpadding="0" cellspacing="0">
    <tr>
        <td style="border-radius:12px; background:#2f5d3a;">
            <a href="{{ $url }}" style="display:inline-block; padding:14px 28px; color:#ffffff; font-size:16px; font-weight:700; text-decoration:none;">{{ $slot }}</a>
        </td>
    </tr>
</table>
