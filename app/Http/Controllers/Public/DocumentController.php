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
        $document = \App\Models\Document::published()->with('fileMedia')->where('slug', $slug)->firstOrFail();
        
        $document->increment('download_count');
        
        if (!$document->fileMedia) {
            abort(404);
        }

        return redirect($document->fileMedia->url);
    }
}

