<?php

namespace App\Support\Preview\Renderers;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\PageComponent;
use App\Support\Preview\PreviewContext;
use Illuminate\Support\Facades\View;

class PagePreviewRenderer
{
    public function render(PreviewContext $context): \Illuminate\Contracts\View\View
    {
        $snapshot = $context->recordSnapshot ?? [];
        $state = $context->normalizedState;

        $merged = array_merge($snapshot, $state);

        $page = new Page();
        $page->forceFill($merged);
        $page->id = $merged['id'] ?? null;

        // Build sections and components in memory
        $sectionsData = $merged['builder_sections'] ?? [];
        $sections = collect();

        $sectionPosition = 0;
        foreach ($sectionsData as $sectionUuid => $sectionItem) {
            $sectionModel = new PageSection();
            $sectionModel->forceFill([
                'id' => $sectionItem['id'] ?? null,
                'name' => $sectionItem['name'] ?? null,
                'layout_type' => $sectionItem['layout_type'] ?? 'single_column',
                'section_settings' => $sectionItem['section_settings'] ?? [],
                'is_visible' => $sectionItem['is_visible'] ?? true,
                'position' => $sectionPosition++,
            ]);

            $components = collect();
            $componentsData = $sectionItem['components'] ?? [];

            $componentPosition = 0;
            foreach ($componentsData as $componentUuid => $componentItem) {
                // Filament builder wraps data in 'type' and 'data' keys
                $type = $componentItem['type'] ?? null;
                $data = $componentItem['data'] ?? [];

                $componentModel = new PageComponent();
                $componentModel->forceFill([
                    'id' => $data['id'] ?? null,
                    'component_type' => $type,
                    'content_data' => $data,
                    'component_settings' => $data['component_settings'] ?? [],
                    'column_position' => $data['column_position'] ?? 0,
                    'is_visible' => $data['is_visible'] ?? true,
                    'position' => $componentPosition++,
                ]);
                $components->push($componentModel);
            }

            $sectionModel->setRelation('components', $components);
            $sections->push($sectionModel);
        }

        $page->setRelation('sections', $sections);

        return View::make('pages.dynamic', [
            'page' => $page,
            'isPreview' => true,
        ]);
    }
}
