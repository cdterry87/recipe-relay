@props(['recipe'])

{{-- One recipe row for emails: a small photo, the title and a few facts. --}}
@php
    $url = $recipe->shareUrl();
    $facts = array_filter([
        App\Models\Recipe::formatMinutes($recipe->totalMinutes()),
        $recipe->hasEnoughRatings() ? number_format($recipe->rating_avg, 1).' ★' : null,
        $recipe->meal_type?->label(),
    ]);
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 12px;">
    <tr>
        <td width="96" valign="top" style="padding:0 16px 0 0;">
            <a href="{{ $url }}" aria-label="{{ $recipe->title }}" style="display:block; width:96px; height:72px; border-radius:10px; background:#f1e8d8; overflow:hidden; text-align:center; line-height:72px; font-size:28px; text-decoration:none;">
                @if ($image = $recipe->imageUrl(400))
                    <img src="{{ $image }}" width="96" height="72" alt="" style="display:block; width:96px; height:72px; object-fit:cover; border:0;">
                @else
                    🍽️
                @endif
            </a>
        </td>
        <td valign="middle">
            <a href="{{ $url }}" style="color:#23291f; font-size:16px; font-weight:700; text-decoration:none;">{{ $recipe->title }}</a>
            @if ($facts)<p style="margin:4px 0 0; font-size:13px; color:#5f6157;">{{ implode(' · ', $facts) }}</p>@endif
        </td>
    </tr>
</table>
