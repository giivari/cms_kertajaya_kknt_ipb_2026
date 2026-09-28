<?php

namespace App\Support;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Mews\Purifier\Facades\Purifier;

final class ContentSecurity
{
    public const SETTING_URL_KEYS = [
        'social_facebook', 'social_instagram', 'social_twitter', 'social_youtube',
        'footer_link_1_url', 'footer_link_2_url',
        'hero_button_1_custom_url', 'hero_button_2_custom_url', 'profil_button_custom_url',
        'potensi_all_custom_url', 'potensi_1_custom_url', 'potensi_2_custom_url', 'potensi_3_custom_url',
        'potensi_1_link', 'potensi_2_link', 'potensi_3_link', 'potensi_all_link',
    ];

    public static function richText(mixed $content): string
    {
        if (is_string($content)) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $content = $decoded;
            }
        }

        if (is_array($content)) {
            $content = RichContentRenderer::make($content)->toHtml();
        }

        if (! is_string($content) || $content === '') {
            return '';
        }

        return Purifier::clean($content, [
            'HTML.Allowed' => 'p[style],div[style],span[style],br,h1[style],h2[style],h3[style],h4[style],h5[style],h6[style],strong,b,em,i,u,s,del,sub,sup,blockquote,pre,code,ul,ol[start],li,hr,a[href|title|target|rel],img[src|alt|title|width|height],figure,figcaption,table,thead,tbody,tfoot,tr,th[colspan|rowspan|style],td[colspan|rowspan|style]',
            'CSS.AllowedProperties' => 'text-align,color,background-color,font-weight,font-style,text-decoration,padding-left',
            'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true],
            'Attr.AllowedFrameTargets' => ['_blank', '_self'],
            'HTML.SafeIframe' => false,
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => false,
        ]);
    }

    // URL validation is separate from HTML escaping. Callers must still use {{ }}
    // (or e()) when placing the returned value inside an HTML attribute.
    public static function isSafeUrl(mixed $url): bool
    {
        if (! is_string($url) || preg_match('/[\x00-\x20\x7f\\\\<>"\']/', $url)) {
            return false;
        }

        if ($url === '' || str_starts_with($url, '#')) {
            return true;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    public static function url(mixed $url): string
    {
        return self::isSafeUrl($url) ? $url : '#';
    }
}
