<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMediaRequest;
use App\Services\MediaProcessingService;

class MediaController extends Controller
{
    public function store(StoreMediaRequest $request, MediaProcessingService $service)
    {
        $media = $service->handleUpload($request->file('file'), $request->validated());

        return response()->json($media, 201);
    }
}
