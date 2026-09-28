<?php

namespace App\Filament\Support;

use App\Models\Media;
use App\Services\Preview\PreviewTemporaryAssets;

class PreviewStateNormalizer
{
    private const PAGE_COMPONENTS = [
        'heading',
        'rich_text',
        'image',
        'gallery',
        'statistics',
        'video',
        'map',
        'documents',
        'cta_button',
        'card_grid',
        'contact_block',
    ];

    private const PAGE_LAYOUTS = [
        'single_column',
        'two_columns',
        'three_columns',
        'hero',
        'full_width',
    ];

    private const PREVIEW_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ];

    public static function normalize(string $type, array $state): array
    {
        return match ($type) {
            'news' => self::news($state),
            'page' => self::page($state),
            'location' => self::location($state),
            'gallery' => self::gallery($state),
            'document' => self::document($state),
            'media' => self::media($state),
            'menu' => self::menu($state),
            'location-category', 'news-category', 'document-category' => self::category($state),
            'settings' => self::settings($state),
            default => [],
        };
    }

    public static function pageComponent(?string $type, array $data): ?array
    {
        if (! in_array($type, self::PAGE_COMPONENTS, true)) {
            return null;
        }

        $visible = self::boolean($data['is_visible'] ?? true);

        $content = match ($type) {
            'heading' => [
                'text' => self::text($data['text'] ?? null),
                'level' => in_array($data['level'] ?? null, ['h1', 'h2', 'h3', 'h4'], true) ? $data['level'] : 'h2',
                'alignment' => in_array($data['alignment'] ?? null, ['left', 'center', 'right'], true) ? $data['alignment'] : 'left',
                'is_visible' => $visible,
            ],
            'rich_text' => [
                'content' => self::richContent($data['content'] ?? null),
                'is_visible' => $visible,
            ],
            'image' => [
                'media_id' => self::mediaId($data['media_id'] ?? null),
                'caption' => self::text($data['caption'] ?? null),
                'alt_text' => self::text($data['alt_text'] ?? null),
                'is_visible' => $visible,
            ],
            'gallery' => [
                'images' => self::mediaIds($data['images'] ?? []),
                'is_visible' => $visible,
            ],
            'documents' => [
                'document_ids' => self::documentIds($data['document_ids'] ?? []),
                // Historical Media IDs stay in their original namespace.
                'documents' => self::positiveIds($data['documents'] ?? []),
                'is_visible' => $visible,
            ],
            'statistics' => [
                'items' => self::rows($data['items'] ?? [], ['label', 'value', 'icon']),
                'is_visible' => $visible,
            ],
            'video' => [
                'video_url' => self::safeVideoUrl($data['video_url'] ?? null),
                'caption' => self::text($data['caption'] ?? null),
                'is_visible' => $visible,
            ],
            'map' => [
                'latitude' => self::coordinate($data['latitude'] ?? null, -90, 90),
                'longitude' => self::coordinate($data['longitude'] ?? null, -180, 180),
                'zoom' => self::integerInRange($data['zoom'] ?? 15, 1, 19, 15),
                'is_visible' => $visible,
            ],
            'cta_button' => [
                'text' => self::text($data['text'] ?? null),
                'url' => self::safeUrl($data['url'] ?? null),
                'style' => in_array($data['style'] ?? null, ['primary', 'secondary', 'outline'], true) ? $data['style'] : 'primary',
                'is_visible' => $visible,
            ],
            'card_grid' => [
                'cards' => collect(is_array($data['cards'] ?? null) ? $data['cards'] : [])
                    ->filter(static fn (mixed $value): bool => is_array($value))
                    ->map(fn (array $card): array => [
                        'title' => self::text($card['title'] ?? null),
                        'description' => self::text($card['description'] ?? null),
                        'link_url' => self::safeUrl($card['link_url'] ?? null),
                    ])
                    ->values()
                    ->all(),
                'is_visible' => $visible,
            ],
            'contact_block' => [
                'email' => self::text($data['email'] ?? null),
                'phone' => self::text($data['phone'] ?? null),
                'address' => self::text($data['address'] ?? null),
                'is_visible' => $visible,
            ],
        };

        return array_merge($content, [
            'id' => self::integer($data['id'] ?? null),
            'column_position' => self::integer($data['column_position'] ?? null),
            'component_settings' => is_array($data['component_settings'] ?? null) ? $data['component_settings'] : [],
        ]);
    }

    private static function news(array $state): array
    {
        return [
            'title' => self::text($state['title'] ?? null),
            'excerpt' => self::text($state['excerpt'] ?? null),
            'content' => self::richContent($state['content'] ?? null),
            'news_category_id' => self::integer($state['news_category_id'] ?? null),
            'featured_media_id' => self::mediaId($state['featured_media_id'] ?? null),
            'status' => self::status($state['status'] ?? null),
            'published_at' => self::safeDate($state['published_at'] ?? null),
        ];
    }

    private static function page(array $state): array
    {
        $sections = collect(is_array($state['builder_sections'] ?? null) ? $state['builder_sections'] : [])
            ->filter(static fn (mixed $value): bool => is_array($value))
            ->map(function (array $section): array {
                $layout = in_array($section['layout_type'] ?? null, self::PAGE_LAYOUTS, true)
                    ? $section['layout_type']
                    : 'single_column';

                $components = collect(is_array($section['components'] ?? null) ? $section['components'] : [])
                    ->filter(static fn (mixed $value): bool => is_array($value))
                    ->map(function (array $component): ?array {
                        $type = is_string($component['type'] ?? null) ? $component['type'] : null;
                        $data = is_array($component['data'] ?? null) ? $component['data'] : [];
                        $normalized = self::pageComponent($type, $data);

                        return $normalized === null ? null : ['type' => $type, 'data' => $normalized];
                    })
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'id' => self::integer($section['id'] ?? null),
                    'name' => self::text($section['name'] ?? null),
                    'layout_type' => $layout,
                    'section_settings' => is_array($section['section_settings'] ?? null) ? $section['section_settings'] : [],
                    'is_visible' => self::boolean($section['is_visible'] ?? true),
                    'components' => $components,
                ];
            })
            ->values()
            ->all();

        return [
            'title' => self::text($state['title'] ?? null),
            'excerpt' => self::text($state['excerpt'] ?? null),
            'seo_title' => self::text($state['seo_title'] ?? null),
            'seo_description' => self::text($state['seo_description'] ?? null),
            'featured_media_id' => self::mediaId($state['featured_media_id'] ?? null),
            'status' => self::status($state['status'] ?? null),
            'published_at' => self::safeDate($state['published_at'] ?? null),
            'builder_sections' => $sections,
        ];
    }

    private static function location(array $state): array
    {
        return [
            'name' => self::text($state['name'] ?? null),
            'location_category_id' => self::integer($state['location_category_id'] ?? null),
            'address' => self::text($state['address'] ?? null),
            'short_description' => self::text($state['short_description'] ?? null),
            'media_id' => self::mediaId($state['media_id'] ?? null),
            'status' => self::status($state['status'] ?? null),
            'published_at' => self::safeDate($state['published_at'] ?? null),
            'latitude' => self::coordinate($state['latitude'] ?? null, -90, 90),
            'longitude' => self::coordinate($state['longitude'] ?? null, -180, 180),
        ];
    }

    private static function gallery(array $state): array
    {
        $items = collect(is_array($state['items'] ?? null) ? $state['items'] : [])
            ->filter(static fn (mixed $value): bool => is_array($value))
            ->map(fn (array $item): array => [
                'media_id' => self::mediaReference($item['media_id'] ?? null, imageOnly: true),
                'id' => self::integer($item['id'] ?? null),
                'caption' => self::text($item['caption'] ?? null),
                'alt_text' => self::text($item['alt_text'] ?? null),
            ])
            ->values()
            ->all();

        return [
            'title' => self::text($state['title'] ?? null),
            'description' => self::text($state['description'] ?? null),
            'cover_media_id' => self::mediaId($state['cover_media_id'] ?? null),
            'status' => self::status($state['status'] ?? null),
            'published_at' => self::safeDate($state['published_at'] ?? null),
            'items' => $items,
        ];
    }

    private static function document(array $state): array
    {
        $upload = collect(is_array($state['document_upload'] ?? null) ? $state['document_upload'] : [$state['document_upload'] ?? null])
            ->first(fn ($candidate): bool => is_array($candidate) && ($candidate['__preview_upload_metadata'] ?? false) === true);

        return [
            'title' => self::text($state['title'] ?? null),
            'description' => self::text($state['description'] ?? null),
            'document_category_id' => self::integer($state['document_category_id'] ?? null),
            'file_media_id' => self::mediaId($state['file_media_id'] ?? null),
            'thumbnail_media_id' => self::mediaId($state['thumbnail_media_id'] ?? null),
            'upload_name' => is_array($upload) ? self::text($upload['name'] ?? null) : null,
            'upload_mime' => is_array($upload) ? self::text($upload['mime'] ?? null) : null,
            'upload_size' => is_array($upload) ? self::integer($upload['size'] ?? null) : null,
            'status' => self::status($state['status'] ?? null),
            'published_at' => self::safeDate($state['published_at'] ?? null),
        ];
    }

    private static function media(array $state): array
    {
        $upload = collect(is_array($state['file'] ?? null) ? $state['file'] : [$state['file'] ?? null])
            ->first(fn ($candidate) => PreviewTemporaryAssets::assetId($candidate) !== null);

        return [
            'file_asset_id' => PreviewTemporaryAssets::assetId($upload),
            'file_mime_type' => is_array($upload) ? ($upload['mime'] ?? null) : null,
            'original_filename' => self::text($state['original_filename'] ?? null),
            'alt_text' => self::text($state['alt_text'] ?? null),
            'caption' => self::text($state['caption'] ?? null),
        ];
    }

    private static function menu(array $state): array
    {
        $menu = [
            'description' => self::text($state['description'] ?? null),
            'items' => self::menuItems($state['items'] ?? []),
        ];

        if (array_key_exists('location', $state)) {
            $menu['location'] = $state['location'] === 'header_menu' ? \App\Models\Menu::HEADER
                : (in_array($state['location'], array_keys(\App\Models\Menu::supportedLocations()), true)
                    ? $state['location'] : \App\Models\Menu::HEADER);
        }

        return $menu;
    }

    private static function menuItems(mixed $items): array
    {
        return collect(is_array($items) ? $items : [])
            ->filter(static fn (mixed $value): bool => is_array($value))
            ->map(fn (array $item): array => [
                'id' => self::integer($item['id'] ?? null),
                'label' => self::text($item['label'] ?? null),
                'link_type' => in_array($item['link_type'] ?? null, array_map(fn (\App\Enums\LinkType $case): string => $case->value, \App\Enums\LinkType::cases()), true)
                    ? $item['link_type'] : null,
                'page_id' => self::integer($item['page_id'] ?? null),
                'custom_url' => self::safeUrl(is_string($item['custom_url'] ?? null) ? $item['custom_url'] : null),
                'target' => ($item['target'] ?? null) === '_blank' || ($item['is_blank'] ?? false) ? '_blank' : '_self',
                'is_visible' => self::boolean($item['is_visible'] ?? true),
                'children' => self::menuItems($item['children'] ?? []),
            ])
            ->values()
            ->all();
    }

    private static function category(array $state): array
    {
        return [
            'name' => self::text($state['name'] ?? null),
            'description' => self::text($state['description'] ?? null),
            'is_active' => self::boolean($state['is_active'] ?? true),
        ];
    }

    private static function settings(array $state): array
    {
        $normalized = [];
        foreach ($state as $key => $value) {
            if ($key === 'service_hours' && is_array($value)) {
                $normalized[$key] = self::rows($value, ['day', 'time']);
                continue;
            }
            if (! is_string($key) || ! preg_match('/^[a-z][a-z0-9_]{0,79}$/D', $key)
                || (! is_scalar($value) && $value !== null)) {
                continue;
            }
            if (in_array($key, \App\Support\ContentSecurity::SETTING_URL_KEYS, true)) {
                $normalized[$key] = self::safeUrl(is_string($value) ? $value : null);
            } elseif (in_array($key, \App\Services\MediaReferenceCoordinator::SETTING_KEYS, true)) {
                $normalized[$key] = self::mediaId($value);
            } else {
                $normalized[$key] = $value;
            }
        }
        return $normalized;
    }

    private static function safeUrl(?string $url): string
    {
        return \App\Support\ContentSecurity::url($url);
    }

    private static function safeVideoUrl(mixed $url): string
    {
        $url = self::safeUrl(is_string($url) ? $url : null);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, ['youtube.com', 'www.youtube.com'], true) ? $url : '#';
    }

    private static function coordinate(mixed $value, float $minimum, float $maximum): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $coordinate = (float) $value;

        return $coordinate >= $minimum && $coordinate <= $maximum ? $coordinate : null;
    }

    private static function safeDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private static function integer(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : null;
    }

    private static function integerInRange(mixed $value, int $minimum, int $maximum, int $default): int
    {
        $integer = self::integer($value);

        return $integer !== null && $integer >= $minimum && $integer <= $maximum ? $integer : $default;
    }

    private static function mediaId(mixed $value): ?int
    {
        $id = self::integer($value);

        return $id !== null && Media::query()->whereKey($id)->exists() ? $id : null;
    }

    private static function mediaIds(mixed $values): array
    {
        return collect(is_array($values) ? $values : [])
            ->map(fn ($value): ?int => self::mediaId($value))
            ->filter()
            ->values()
            ->all();
    }

    private static function documentIds(mixed $values): array
    {
        return collect(is_array($values) ? $values : [])
            ->map(fn ($value) => filter_var($value, FILTER_VALIDATE_INT) === false ? null : (int) $value)
            ->filter(fn ($id) => $id !== null && $id > 0 && \App\Models\Document::query()->whereKey($id)->exists())
            ->values()->all();
    }

    private static function positiveIds(mixed $values): array
    {
        return collect(is_array($values) ? $values : [])
            ->map(fn ($value): ?int => self::integer($value))
            ->filter(fn (?int $id): bool => $id !== null && $id > 0)
            ->values()->all();
    }

    private static function mediaReference(mixed $value, bool $imageOnly = false): int|array|null
    {
        if (PreviewTemporaryAssets::assetId($value) !== null) {
            return ! $imageOnly || str_starts_with((string) ($value['mime'] ?? ''), 'image/') ? $value : null;
        }

        return self::mediaId($value);
    }

    private static function rows(mixed $rows, array $keys): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->filter(static fn (mixed $value): bool => is_array($value))
            ->map(fn (array $row): array => collect($keys)
                ->mapWithKeys(fn (string $key): array => [$key => self::text($row[$key] ?? null)])
                ->all())
            ->values()
            ->all();
    }

    private static function status(mixed $status): string
    {
        return in_array($status, ['draft', 'published', 'archived'], true) ? $status : 'draft';
    }

    private static function richContent(mixed $content): string
    {
        if (is_array($content)) {
            $content = self::preserveTemporaryImages($content);
            return \App\Support\ContentSecurity::richText($content);
        }

        if (is_string($content)) {
            $trimmed = trim($content);
            if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $decoded = self::preserveTemporaryImages($decoded);
                    return \App\Support\ContentSecurity::richText($decoded);
                }
            }
            return \App\Support\ContentSecurity::richText($content);
        }

        return '';
    }

    private static function preserveTemporaryImages(array $content): array
    {
        if (isset($content['type']) && $content['type'] === 'image' && isset($content['attrs']['src'])) {
            $sourcePath = parse_url((string) $content['attrs']['src'], PHP_URL_PATH);
            if (is_string($sourcePath) && str_contains($sourcePath, '/livewire/preview-file/')) {
                // RichEditor upload state is not a token-owned preview asset.
                // Do not carry its independently signed temporary URL forward.
                unset($content['attrs']['src'], $content['attrs']['id']);
            }
        }

        if (isset($content['content']) && is_array($content['content'])) {
            foreach ($content['content'] as $key => $child) {
                if (is_array($child)) {
                    $content['content'][$key] = self::preserveTemporaryImages($child);
                }
            }
        }

        return $content;
    }

    private static function boolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
    }
}
