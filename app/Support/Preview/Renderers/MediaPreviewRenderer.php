<?php

namespace App\Support\Preview\Renderers;

use App\Support\Preview\PreviewContext;
use Illuminate\Support\Facades\View;

class MediaPreviewRenderer
{
    public function render(PreviewContext $context)
    {
        $state = $context->normalizedState;
        $assetId = $state['file_asset_id'] ?? null;
        $state['file_url'] = is_string($assetId) && isset($context->routeTokenMetadata['token'])
            ? route('admin.preview.asset', ['token' => $context->routeTokenMetadata['token'], 'assetToken' => $assetId])
            : null;
        if ($state['file_url'] === null && $context->mode === 'edit' && isset($context->recordSnapshot['id'])) {
            $media = \App\Models\Media::query()->find($context->recordSnapshot['id']);
            if ($media && \Illuminate\Support\Facades\Gate::allows('update', $media)) {
                $state['file_url'] = route('admin.media.original', $media);
                $state['file_mime_type'] = $media->mime_type;
            }
        }

        return View::make('public.preview.media', [
            'state' => $state,
        ]);
    }
}
