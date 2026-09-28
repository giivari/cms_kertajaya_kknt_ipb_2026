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
            // Filament surrounds create/edit hooks with its own transaction. This
            // nested transaction is therefore a savepoint when invoked from the
            // panel, while keeping this service atomic for other callers.
            $lockedPage = Page::query()->lockForUpdate()->findOrFail($page->getKey());
            app(DocumentReferenceCoordinator::class)->lockReferences($this->referencedDocumentIds($sectionsData));
            app(MediaReferenceCoordinator::class)->lockReferences($this->referencedMediaIds($sectionsData));
            $existingSections = $lockedPage->sections()
                ->lockForUpdate()
                ->with(['components' => fn ($query) => $query->lockForUpdate()])
                ->get()
                ->keyBy('id');

            $this->reserveSectionPositions($lockedPage);

            $submittedSectionIds = [];
            $sectionPosition = 0;
            foreach ($sectionsData as $sectionData) {
                $sectionId = $sectionData['id'] ?? null;
                $section = $this->existingSection($existingSections, $sectionId);

                if ($sectionId !== null && ! $section) {
                    throw new \InvalidArgumentException('Page builder section does not belong to this page.');
                }

                $section ??= new PageSection(['page_id' => $lockedPage->id]);
                $section->name = $sectionData['name'] ?? null;
                $section->layout_type = $sectionData['layout_type'] ?? 'single_column';
                $section->position = $sectionPosition++;
                $section->section_settings = $sectionData['section_settings'] ?? $section->section_settings ?? [];
                $section->is_visible = $sectionData['is_visible'] ?? $section->is_visible ?? true;
                $section->save();

                $submittedSectionIds[] = $section->id;
                $this->saveComponents($section, $sectionData['components'] ?? []);
            }

            foreach ($existingSections->except($submittedSectionIds) as $section) {
                $section->components()->delete();
                $section->delete();
            }
        });
    }

    protected function saveComponents(PageSection $section, array $componentsData): void
    {
        $existingComponents = $section->components()
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $this->reserveComponentPositions($section);

        $submittedComponentIds = [];
        $position = 0;
        foreach ($componentsData as $componentData) {
            // Filament Builder passes data in format: ['type' => 'heading', 'data' => [...]]
            $type = $componentData['type'] ?? null;
            $data = $componentData['data'] ?? [];
            if (! is_string($type) || ! is_array($data)) {
                throw new \InvalidArgumentException('Invalid page builder component payload.');
            }

            $componentId = $data['id'] ?? null;
            $component = $this->existingComponent($existingComponents, $componentId);
            if ($componentId !== null && ! $component) {
                throw new \InvalidArgumentException('Page builder component does not belong to this section.');
            }

            if ($type === 'rich_text') {
                $data['content'] = \App\Support\ContentSecurity::richText($data['content'] ?? '');
            }

            $component ??= new PageComponent(['section_id' => $section->id]);

            if ($type === 'documents') {
                $ids = $data['document_ids'] ?? [];
                if (! is_array($ids) || collect($ids)->contains(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 1)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['document_ids' => 'Pilihan dokumen tidak valid.']);
                }
                $data['document_ids'] = array_values(array_unique(array_map('intval', $ids)));
                if ($data['document_ids'] !== []) {
                    unset($data['documents']);
                } elseif ($component->exists) {
                    // A legacy Media ID is never reinterpreted as a Document ID.
                    // Keep unresolved old payloads on unrelated editor saves.
                    $legacy = $component->content_data['documents'] ?? null;
                    if ($legacy !== null) {
                        $data['documents'] = $legacy;
                    }
                } else {
                    unset($data['documents']);
                }
            }

            $component->component_type = $type;
            // Column and component settings are not editable in the current
            // Builder schema. Retain them for existing rows instead of silently
            // collapsing metadata during an unrelated edit.
            $component->column_position = $component->exists ? $component->column_position : 1;
            $component->position = $position++;

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
            unset($data['id'], $data['column_position'], $data['component_settings'], $data['is_visible']);

            $component->content_data = $data;
            $component->component_settings = $component->component_settings ?? [];
            $component->is_visible = $component->exists ? $component->is_visible : true;
            $component->save();
            $submittedComponentIds[] = $component->id;
        }

        foreach ($existingComponents->except($submittedComponentIds) as $component) {
            $component->delete();
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
                
                // The Builder UUID is only a Livewire state key. The explicit
                // hidden ID is the stable relational identity used during save.
                $data['id'] = $component->id;
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

        return \App\Support\ContentSecurity::url($url);
    }

    /** @param \Illuminate\Support\Collection<int, PageSection> $sections */
    private function existingSection($sections, mixed $id): ?PageSection
    {
        return filter_var($id, FILTER_VALIDATE_INT) !== false ? $sections->get((int) $id) : null;
    }

    /** @param \Illuminate\Support\Collection<int, PageComponent> $components */
    private function existingComponent($components, mixed $id): ?PageComponent
    {
        return filter_var($id, FILTER_VALIDATE_INT) !== false ? $components->get((int) $id) : null;
    }

    private function reserveSectionPositions(Page $page): void
    {
        $count = $page->sections()->count();
        if ($count === 0) {
            return;
        }

        $offset = (int) $page->sections()->max('position') + $count + 1;
        $page->sections()->update(['position' => DB::raw("position + {$offset}")]);
    }

    private function reserveComponentPositions(PageSection $section): void
    {
        $count = $section->components()->count();
        if ($count === 0) {
            return;
        }

        $offset = (int) $section->components()->max('position') + $count + 1;
        $section->components()->update(['position' => DB::raw("position + {$offset}")]);
    }

    /** @return array<int, mixed> */
    private function referencedMediaIds(array $sectionsData): array
    {
        $ids = [];
        foreach ($sectionsData as $section) {
            foreach (($section['components'] ?? []) as $component) {
                $data = is_array($component['data'] ?? null) ? $component['data'] : [];
                $ids[] = $data['media_id'] ?? null;
                foreach (['images'] as $key) {
                    foreach ((array) ($data[$key] ?? []) as $value) {
                        $ids[] = is_array($value) ? ($value['media_id'] ?? $value['id'] ?? null) : $value;
                    }
                }
            }
        }

        return $ids;
    }

    private function referencedDocumentIds(array $sectionsData): array
    {
        $ids = [];
        foreach ($sectionsData as $section) {
            foreach (($section['components'] ?? []) as $component) {
                if (($component['type'] ?? null) !== 'documents') {
                    continue;
                }
                $data = $component['data'] ?? [];
                foreach ((array) ($data['document_ids'] ?? []) as $id) {
                    if (filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 1) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['document_ids' => 'Pilihan dokumen tidak valid.']);
                    }
                    $ids[] = (int) $id;
                }
            }
        }

        return $ids;
    }
}
