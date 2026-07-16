<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::published()->with(['category', 'thumbnailMedia']);

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        $documents = $query->latest('published_at')->paginate(12);

        return view('public.documents.index', compact('documents'));
    }

    public function download($slug)
    {
        $document = Document::published()->with('fileMedia.derivatives')->where('slug', $slug)->firstOrFail();

        $media = $document->fileMedia;
        if (! $media || $media->processing_status->value !== 'completed' || $media->invisible_watermark_status->value !== 'verified') {
            abort(404, 'Dokumen tidak tersedia atau belum disetujui.');
        }

        $derivative = $media->derivatives()->where('derivative_type', 'public')->first();
        if (! $derivative) {
            abort(404, 'File publik tidak ditemukan.');
        }

        $disk = Storage::disk($derivative->disk);
        $path = $derivative->directory ? $derivative->directory.'/'.$derivative->filename : $derivative->filename;
        if (! $disk->exists($path)) {
            abort(404, 'File hilang.');
        }

        $document->increment('download_count');

        return response()->streamDownload(function () use ($disk, $path) {
            echo $disk->get($path);
        }, Str::slug($document->title).'.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function preview($slug)
    {
        $document = Document::with('fileMedia.derivatives')->where('slug', $slug)->firstOrFail();

        $media = $document->fileMedia;
        if (! $media || $media->processing_status->value !== 'completed' || $media->invisible_watermark_status->value !== 'verified') {
            abort(404, 'Dokumen tidak tersedia atau belum disetujui.');
        }

        $derivative = $media->derivatives()->where('derivative_type', 'public')->first();
        if (! $derivative) {
            abort(404, 'File publik tidak ditemukan.');
        }

        $disk = Storage::disk($derivative->disk);
        $path = $derivative->directory ? $derivative->directory.'/'.$derivative->filename : $derivative->filename;
        if (! $disk->exists($path)) {
            abort(404, 'File hilang.');
        }

        return response()->streamDownload(function () use ($disk, $path) {
            echo $disk->get($path);
        }, Str::slug($document->title).'.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
