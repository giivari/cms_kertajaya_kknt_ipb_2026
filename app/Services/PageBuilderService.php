<?php

namespace App\Services;

use App\Models\Page;
use App\Models\PageComponent;
use App\Models\PageSection;

class PageBuilderService
{
    /**
     * Normalize Filament Repeater/Builder state into relational tables.
     */
    public function saveSectionsAndComponents(Page $page, array $sectionsData): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($page, $sectionsData) {
            // Get existing section IDs to determine which ones to delete
        $existingSectionIds = $page->sections()->pluck('id')->toArray();
        $keptSectionIds = [];

        foreach ($sectionsData as $sectionIndex => $sectionData) {
            $sectionModel = null;
            
            // Check if this section already exists (usually Filament passes an ID if editing a repeater item, 
            // but we might just use the array key or handle it differently.
            // Filament repeaters use UUIDs as keys, so we can store that or just recreate.
            // The cleanest way is to recreate or update based on a hidden ID field.
            
            $sectionId = $sectionData['id'] ?? null;

            if ($sectionId && in_array($sectionId, $existingSectionIds)) {
                $sectionModel = PageSection::find($sectionId);
                $keptSectionIds[] = $sectionId;
            } else {
                $sectionModel = new PageSection();
                $sectionModel->page_id = $page->id;
            }

            $sectionModel->name = $sectionData['name'] ?? null;
            $sectionModel->layout_type = $sectionData['layout_type'] ?? 'single_column';
            $sectionModel->position = $sectionIndex;
            $sectionModel->section_settings = $sectionData['section_settings'] ?? [];
            $sectionModel->is_visible = $sectionData['is_visible'] ?? true;
            $sectionModel->save();
            
            if (!$sectionId) {
                $keptSectionIds[] = $sectionModel->id;
            }

            $this->saveComponents($sectionModel, $sectionData['components'] ?? []);
        }

            // Delete sections that were removed
            $sectionsToDelete = array_diff($existingSectionIds, $keptSectionIds);
            if (!empty($sectionsToDelete)) {
                PageSection::whereIn('id', $sectionsToDelete)->delete();
            }
        });
    }

    protected function saveComponents(PageSection $section, array $componentsData): void
    {
        $existingComponentIds = $section->components()->pluck('id')->toArray();
        $keptComponentIds = [];

        $position = 0;
        foreach ($componentsData as $componentUuid => $componentData) {
            // Filament Builder passes data in format: ['type' => 'heading', 'data' => [...]]
            $type = $componentData['type'];
            $data = $componentData['data'];
            
            $componentId = $data['id'] ?? null;

            if ($componentId && in_array($componentId, $existingComponentIds)) {
                $componentModel = PageComponent::find($componentId);
                $keptComponentIds[] = $componentId;
            } else {
                $componentModel = new PageComponent();
                $componentModel->section_id = $section->id;
            }

            $componentModel->component_type = $type;
            $componentModel->column_position = isset($data['column_position']) ? (int) $data['column_position'] : 1;
            $componentModel->position = $position++;

            if ($type === 'rich_text' && isset($data['content'])) {
                $data['content'] = clean($data['content']);
            }

            if ($type === 'cta_button' && isset($data['url'])) {
                $data['url'] = $this->sanitizeUrl($data['url']);
            }
            if ($type === 'card_grid' && isset($data['cards']) && is_array($data['cards'])) {
                foreach ($data['cards'] as &$card) {
                    if (!empty($card['link_url'])) {
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

            if (!$componentId) {
                $keptComponentIds[] = $componentModel->id;
            }
        }

        $componentsToDelete = array_diff($existingComponentIds, $keptComponentIds);
        if (!empty($componentsToDelete)) {
            PageComponent::whereIn('id', $componentsToDelete)->delete();
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
                $data['id'] = $component->id;
                $data['column_position'] = $component->column_position;
                $data['component_settings'] = $component->component_settings;
                $data['is_visible'] = $component->is_visible;

                // Filament Builder format uses a UUID for the array key usually, 
                // but sequential array works for setting state.
                $sectionData['components'][(string) \Illuminate\Support\Str::uuid()] = [
                    'type' => $component->component_type,
                    'data' => $data,
                ];
            }

            // Filament Repeater expects UUID keys
            $state[(string) \Illuminate\Support\Str::uuid()] = $sectionData;
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
