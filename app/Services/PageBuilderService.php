<?php

namespace App\Services;

use App\Models\Page;
use App\Models\PageComponent;
use App\Models\PageSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PageBuilderService
{
    /**
     * Normalize Filament Repeater/Builder state into relational tables.
     */
    public function saveSectionsAndComponents(Page $page, array $sectionsData): void
    {
        DB::transaction(function () use ($page, $sectionsData) {
            // Delete all existing sections (and their components via cascade or manual delete)
            // to avoid unique constraint violations on position indexes.
            foreach ($page->sections as $section) {
                $section->components()->delete();
            }
            $page->sections()->delete();

            $sectionPosition = 0;
            foreach ($sectionsData as $sectionUuid => $sectionData) {
                $sectionModel = new PageSection;
                $sectionModel->page_id = $page->id;
                $sectionModel->name = $sectionData['name'] ?? null;
                $sectionModel->layout_type = $sectionData['layout_type'] ?? 'single_column';
                $sectionModel->position = $sectionPosition++;
                $sectionModel->section_settings = $sectionData['section_settings'] ?? [];
                $sectionModel->is_visible = $sectionData['is_visible'] ?? true;
                $sectionModel->save();

                $this->saveComponents($sectionModel, $sectionData['components'] ?? []);
            }
        });
    }

    protected function saveComponents(PageSection $section, array $componentsData): void
    {
        // Delete all existing components first to avoid unique constraint violations
        // on the (section_id, column_position, position) unique index.
        // Components will be fully recreated from the builder state.
        $section->components()->delete();

        $position = 0;
        foreach ($componentsData as $componentUuid => $componentData) {
            // Filament Builder passes data in format: ['type' => 'heading', 'data' => [...]]
            $type = $componentData['type'];
            $data = $componentData['data'];

            $componentModel = new PageComponent;
            $componentModel->section_id = $section->id;

            $componentModel->component_type = $type;
            $componentModel->column_position = isset($data['column_position']) ? (int) $data['column_position'] : 1;
            $componentModel->position = $position++;

            if ($type === 'cta_button' && isset($data['url'])) {
                $data['url'] = $this->sanitizeUrl($data['url']);
            }
            if ($type === 'card_grid' && isset($data['cards']) && is_array($data['cards'])) {
                foreach ($data['cards'] as &$card) {
                    if (! empty($card['link_url'])) {
                        $card['link_url'] = $this->sanitizeUrl($card['link_url']);
                    }
                }
            }

            // Extract settings from data if we want to separate them, or keep them all in content_data
            $settings = $data['component_settings'] ?? [];
            unset($data['component_settings'], $data['id'], $data['column_position']);

            $componentModel->content_data = $data;
            $componentModel->component_settings = $settings;
            $componentModel->is_visible = $data['is_visible'] ?? true;
            $componentModel->save();
        }
    }

    /**
     * Reconstruct Filament state from relational tables.
     */
    public function reconstructBuilderState(Page $page): array
    {
        $state = [];

        foreach ($page->sections as $section) {
            $sectionData = [
                'id' => $section->id,
                'name' => $section->name,
                'layout_type' => $section->layout_type,
                'section_settings' => $section->section_settings,
                'is_visible' => $section->is_visible,
                'components' => [],
            ];

            foreach ($section->components as $component) {
                $data = $component->content_data ?? [];
                
                // Do not inject id, column_position, component_settings, and is_visible into data
                // as Filament Builder blocks will strip them or reject the block if undeclared.
                // Filament Builder uses the UUID key to identify the block, and we can just recreate
                // the components on save (since PageBuilderService deletes missing ones).

                // Filament Builder format uses a UUID for the array key usually,
                // but sequential array works for setting state.
                $sectionData['components'][(string) Str::uuid()] = [
                    'type' => $component->component_type,
                    'data' => $data,
                ];
            }

            // Filament Repeater expects UUID keys
            $state[(string) Str::uuid()] = $sectionData;
        }

        return $state;
    }

    protected function sanitizeUrl(?string $url): ?string
    {
        if (empty($url)) {
            return $url;
        }

        // Reject javascript: and vbscript: URLs
        if (preg_match('/^(javascript|vbscript):/i', trim($url))) {
            return '#';
        }

        return $url;
    }
}
