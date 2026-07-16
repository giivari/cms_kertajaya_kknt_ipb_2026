<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = \App\Models\Document::published()->with(['category', 'thumbnailMedia'])->latest('published_at')->paginate(12);
        return view('public.documents.index', compact('documents'));
    }

    public function download($slug)
    {
        $document = \App\Models\Document::published()->with('fileMedia.derivatives')->where('slug', $slug)->firstOrFail();
        
        $media = $document->fileMedia;
        if (!$media || $media->processing_status->value !== 'completed' || !in_array($media->invisible_watermark_status->value, ['verified', 'unsupported'])) {
            abort(404, 'Dokumen tidak tersedia atau belum disetujui.');
        }

        $derivative = $media->derivatives()->where('derivative_type', 'public')->first();
        if (!$derivative) {
            abort(404, 'File publik tidak ditemukan.');
        }

        $document->increment('download_count');

        $disk = \Illuminate\Support\Facades\Storage::disk($derivative->disk);
        $path = $derivative->directory ? $derivative->directory . '/' . $derivative->filename : $derivative->filename;
        if (!$disk->exists($path)) {
            abort(404, 'File hilang.');
        }

        return response()->streamDownload(function () use ($disk, $path) {
            echo $disk->get($path);
        }, \Illuminate\Support\Str::slug($document->title) . '.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

