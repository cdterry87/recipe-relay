<?php

namespace App\Support;

use App\Models\Recipe;

/**
 * Share addresses for a recipe: email and the social networks we offer. Nothing is sent
 * anywhere until the visitor clicks one. Unlisted recipes share their secret link.
 */
final class ShareLinks
{
    /**
     * @return list<array{network: string, label: string, href: string}>
     */
    public static function for(Recipe $recipe): array
    {
        $url = $recipe->shareUrl();
        $text = "{$recipe->title} on ".config('app.name');
        $q = fn (array $params) => http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $links = [
            ['network' => 'email', 'label' => 'Email', 'href' => 'mailto:?'.$q([
                'subject' => $text,
                'body' => "I thought you'd like this recipe: {$recipe->title}\n\n{$url}",
            ])],
            ['network' => 'facebook', 'label' => 'Facebook', 'href' => 'https://www.facebook.com/sharer/sharer.php?'.$q(['u' => $url])],
            ['network' => 'x', 'label' => 'X', 'href' => 'https://x.com/intent/post?'.$q(['text' => $text, 'url' => $url])],
        ];

        if ($image = $recipe->imageUrl(1600)) {
            $links[] = ['network' => 'pinterest', 'label' => 'Pinterest', 'href' => 'https://pinterest.com/pin/create/button/?'.$q([
                'url' => $url,
                'media' => $image,
                'description' => $text,
            ])];
        }

        $links[] = ['network' => 'whatsapp', 'label' => 'WhatsApp', 'href' => 'https://wa.me/?'.$q(['text' => "{$text} {$url}"])];
        $links[] = ['network' => 'reddit', 'label' => 'Reddit', 'href' => 'https://www.reddit.com/submit?'.$q(['url' => $url, 'title' => $recipe->title])];

        return $links;
    }
}
