<?php

namespace App\Support\Preview\Renderers;

use App\Support\Preview\PreviewContext;
use Illuminate\Contracts\View\View;

final class CategoryPreviewRenderer
{
    public function render(PreviewContext $context): View
    {
        return view('public.preview.category', [
            'type' => $context->previewType,
            'state' => array_merge($context->recordSnapshot ?? [], $context->normalizedState),
        ]);
    }
}
