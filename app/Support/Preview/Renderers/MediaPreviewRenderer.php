<?php

namespace App\Support\Preview\Renderers;

use App\Support\Preview\PreviewContext;
use Illuminate\Support\Facades\View;

class MediaPreviewRenderer
{
    public function render(PreviewContext $context)
    {
        $state = $context->normalizedState;

        return View::make('public.preview.media', [
            'state' => $state,
        ]);
    }
}
