<?php

namespace App\Support\Preview\Renderers;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Media;
use App\Support\Preview\PreviewContext;
use Illuminate\Support\Facades\View;

class DocumentPreviewRenderer
{
    public function render(PreviewContext $context): \Illuminate\Contracts\View\View
    {
        $snapshot = $context->recordSnapshot ?? [];
        $state = $context->normalizedState;

        $merged = array_merge($snapshot, $state);

        $document = new Document();
        $document->forceFill($merged);
        $document->id = $merged['id'] ?? null;

        $category = null;
        if (!empty($merged['document_category_id'])) {
            $category = DocumentCategory::find($merged['document_category_id']);
        }
        $document->setRelation('category', $category);

        $fileMedia = null;
        if (!empty($merged['file_media_id'])) {
            $fileMedia = Media::find($merged['file_media_id']);
        }
        $document->setRelation('fileMedia', $fileMedia);

        // Since Document has no public detail page, we render a placeholder preview
        // or a simulated card view for the preview iframe.
        return View::make('public.preview.document_placeholder', [
            'document' => $document,
            'isPreview' => true,
        ]);
    }
}
