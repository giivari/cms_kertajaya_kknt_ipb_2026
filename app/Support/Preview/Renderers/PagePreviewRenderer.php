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
                'name' => $sectionItem['name'] ?? null,
                'layout_type' => $sectionItem['layout_type'] ?? 'single_column',
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
                    'component_type' => $type,
                    'content_data' => $data,
                    'is_visible' => true,
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
