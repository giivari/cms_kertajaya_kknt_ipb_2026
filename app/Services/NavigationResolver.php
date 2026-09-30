<?php

namespace App\Services;

use App\Enums\LinkType;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Support\ContentSecurity;
use Illuminate\Support\Collection;

/** Resolves the same menu shape for public views and a menu preview overlay. */
final class NavigationResolver
{
    public function forLocation(string $location, ?array $previewState = null, ?array $recordSnapshot = null): ?Menu
    {
        if (! array_key_exists($location, Menu::supportedLocations())) {
            return null;
        }

        // Legacy editor state may still refer to the primary location by its
        // old name; normalized previews always carry current destinations.
        $previewLocation = $previewState['location'] ?? $recordSnapshot['location'] ?? null;
        if ($previewLocation === 'header_menu') {
            $previewLocation = Menu::HEADER;
        }

        if ($previewState !== null && $previewLocation === $location) {
            $menu = new Menu;
            $menu->forceFill(array_diff_key(array_merge($recordSnapshot ?? [], $previewState), ['items' => true]));
            $menu->setRelation('items', $this->previewItems($previewState['items'] ?? []));

            return $menu;
        }

        $menu = Menu::query()->where('location', $location)->with([
            'items' => fn ($query) => $query->where('is_visible', true)->orderBy('position')->orderBy('id'),
            'items.children' => fn ($query) => $query->where('is_visible', true)->orderBy('position')->orderBy('id'),
            'items.page',
            'items.children.page',
        ])->first();

        if ($menu) {
            $menu->setRelation('items', $this->eligibleItems($menu->items));
        }

        return $menu;
    }

    /** @param array<int|string, array<string, mixed>> $items */
    private function previewItems(array $items): Collection
    {
        return $this->eligibleItems(collect($items)->map(function (array $item, $index): MenuItem {
            $children = $item['children'] ?? [];
            unset($item['children']);

            $model = new MenuItem;
            $model->forceFill($item);
            $model->position = $index;
            $model->setRelation('children', collect($children)->map(function (array $child, $childIndex): MenuItem {
                $model = new MenuItem;
                $model->forceFill($child);
                $model->position = $childIndex;
                $model->setRelation('children', collect());

                return $model;
            }));

            return $model;
        }), collect($items)->contains(fn (array $item): bool => ! empty($item['link_type'])));
    }

    /** @param Collection<int, MenuItem> $items */
    private function eligibleItems(Collection $items, bool $checkDestinations = true): Collection
    {
        return $items->filter(function (MenuItem $item) use ($checkDestinations): bool {
            if (! $item->is_visible || ($checkDestinations && ! $this->hasEligibleDestination($item))) {
                return false;
            }

            $item->setRelation('children', $this->eligibleItems(collect($item->getRelationValue('children')), $checkDestinations));

            return true;
        })->values();
    }

    private function hasEligibleDestination(MenuItem $item): bool
    {
        $type = $item->getAttributes()['link_type'] ?? $item->getRawOriginal('link_type');

        if ($type === LinkType::PAGE->value) {
            return $item->page?->isPublished() === true;
        }

        if ($type === LinkType::CUSTOM->value) {
            $url = $item->custom_url;

            return is_string($url)
                && ContentSecurity::isSafeUrl($url)
                && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
        }

        return in_array($type, [
            LinkType::HOME->value,
            LinkType::NEWS_INDEX->value,
            LinkType::GALLERY_INDEX->value,
            LinkType::DOCUMENT_INDEX->value,
            LinkType::MAP->value,
            LinkType::CONTACT->value,
        ], true);
    }
}
