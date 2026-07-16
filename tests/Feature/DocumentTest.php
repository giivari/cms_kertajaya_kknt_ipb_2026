<?php

use App\Models\Document;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('documents can be viewed and downloaded', function () {
    $media = Media::create([
        'disk' => 'public',
        'directory' => 'documents',
        'filename' => 'test.pdf',
        'original_filename' => 'test.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'size' => 1024,
        'processing_status' => 'completed',
        'invisible_watermark_status' => 'unsupported',
    ]);

    $doc = Document::create([
        'title' => 'Test Document',
        'slug' => 'test-document',
        'file_media_id' => $media->id,
        'status' => 'published',
        'published_at' => now(),
        'download_count' => 0,
    ]);

    $response = $this->get('/dokumen');
    $response->assertStatus(200);
    $response->assertSee('Test Document');

    $response = $this->get('/dokumen/test-document/download');
    $response->assertRedirect($media->url);

    expect($doc->fresh()->download_count)->toBe(1);
});

test('unpublished documents are not visible', function () {
    $media = Media::create([
        'disk' => 'public',
        'directory' => 'documents',
        'filename' => 'hidden.pdf',
        'original_filename' => 'hidden.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'size' => 1024,
        'processing_status' => 'completed',
        'invisible_watermark_status' => 'unsupported',
    ]);

    Document::create([
        'title' => 'Hidden Document',
        'slug' => 'hidden-document',
        'file_media_id' => $media->id,
        'status' => 'archived',
    ]);

    $response = $this->get('/dokumen');
    $response->assertDontSee('Hidden Document');

    $response = $this->get('/dokumen/hidden-document/download');
    $response->assertStatus(404);
});
