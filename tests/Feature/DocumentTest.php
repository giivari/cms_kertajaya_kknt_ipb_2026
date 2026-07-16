<?php

use App\Models\Document;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('documents can be viewed and downloaded safely', function () {
    Storage::fake('local');
    $media = Media::create([
        'disk' => 'local',
        'directory' => 'documents',
        'filename' => 'test.pdf',
        'original_filename' => 'test.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'size' => 1024,
        'processing_status' => 'completed',
        'invisible_watermark_status' => 'verified',
    ]);
    $derivative = $media->derivatives()->create([
        'derivative_type' => 'public',
        'disk' => 'local',
        'filename' => 'test-public.pdf',
        'mime_type' => 'application/pdf',
        'size' => 512,
    ]);

    Storage::disk('local')->put('test-public.pdf', 'fake pdf content');

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
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('Content-Disposition', 'attachment; filename=test-document.pdf');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');

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
        'invisible_watermark_status' => 'verified',
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

test('documents with unverified media cannot be downloaded', function () {
    $media = Media::create([
        'disk' => 'public',
        'directory' => 'documents',
        'filename' => 'unverified.pdf',
        'original_filename' => 'unverified.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'size' => 1024,
        'processing_status' => 'completed',
        'invisible_watermark_status' => 'unsupported',
    ]);

    Document::create([
        'title' => 'Unverified Document',
        'slug' => 'unverified-document',
        'file_media_id' => $media->id,
        'status' => 'published',
        'published_at' => now(),
    ]);

    $response = $this->get('/dokumen/unverified-document/download');
    $response->assertStatus(404);
});

test('multiple downloads safely increment download count', function () {
    Storage::fake('local');
    $media = Media::create([
        'disk' => 'local',
        'directory' => 'documents',
        'filename' => 'test-concurrent.pdf',
        'original_filename' => 'test-concurrent.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'size' => 1024,
        'processing_status' => 'completed',
        'invisible_watermark_status' => 'verified',
    ]);
    $derivative = $media->derivatives()->create([
        'derivative_type' => 'public',
        'disk' => 'local',
        'filename' => 'test-public-concurrent.pdf',
        'mime_type' => 'application/pdf',
        'size' => 512,
    ]);

    Storage::disk('local')->put('test-public-concurrent.pdf', 'fake pdf content');

    $doc = Document::create([
        'title' => 'Test Concurrent Document',
        'slug' => 'test-concurrent-document',
        'file_media_id' => $media->id,
        'status' => 'published',
        'published_at' => now(),
        'download_count' => 0,
    ]);

    $this->get('/dokumen/test-concurrent-document/download');
    $this->get('/dokumen/test-concurrent-document/download');
    $this->get('/dokumen/test-concurrent-document/download');

    expect($doc->fresh()->download_count)->toBe(3);
});
