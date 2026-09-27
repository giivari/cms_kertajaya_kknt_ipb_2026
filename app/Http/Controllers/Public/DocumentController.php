<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\DocumentDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        $documents = $query->latest('published_at')->paginate(12)->withQueryString();

        return view('public.documents.index', compact('documents'));
    }

    public function download(string $slug, DocumentDeliveryService $delivery)
    {
        $document = Document::published()->with('fileMedia')->where('slug', $slug)->firstOrFail();
        $file = $delivery->resolve($document);
        abort_unless($file, 404, 'Dokumen tidak tersedia.');

        $document->increment('download_count');

        return $this->fileResponse($document, $file);
    }

    public function preview(string $slug, DocumentDeliveryService $delivery)
    {
        $document = Document::with('fileMedia')->where('slug', $slug)->firstOrFail();
        Gate::authorize('view', $document);
        $file = $delivery->resolve($document, public: false);
        abort_unless($file, 404, 'Dokumen tidak tersedia.');

        return $this->fileResponse($document, $file);
    }

    private function fileResponse(Document $document, array $file): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filename = (Str::slug($document->title) ?: 'dokumen').'.'.$file['extension'];
        $response = response()->download($file['path'], $filename, [
            'Content-Type' => $file['mime'],
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);

        // Symfony BinaryFileResponse can add `public` during preparation.
        return $response->setPrivate();
    }
}
