<?php

use App\Models\Document;
use App\Models\Media;
use App\Services\DocumentFilePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function documentTestPdfBytes(): string
{
    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 100 100] /Contents 4 0 R >>',
        "<< /Length 0 >>\nstream\n\nendstream",
    ];
    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $number => $body) {
        $offsets[] = strlen($pdf);
        $pdf .= ($number + 1)." 0 obj\n".$body."\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 5\n0000000000 65535 f \n";
    foreach ($offsets as $offset) {
        $pdf .= sprintf('%010d 00000 n ', $offset)."\n";
    }

    return $pdf."trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
}

function managedDocumentFixture(string $title, string $status = 'published'): array
{
    $bytes = documentTestPdfBytes();
    $filename = 'document-'.uniqid().'.pdf';
    $path = 'originals/'.$filename;
    Storage::disk('local')->put($path, $bytes);
    $validated = app(DocumentFilePolicy::class)->inspect(Storage::disk('local')->path($path), $filename);
    $media = Media::create([
        'disk' => 'local', 'directory' => 'originals', 'filename' => $filename,
        'original_filename' => $filename, 'mime_type' => $validated['mime'],
        'extension' => $validated['extension'], 'size' => $validated['size'],
        'checksum' => $validated['checksum'],
        'metadata' => ['document_validation' => ['version' => 1, 'format' => 'pdf']],
        'processing_status' => 'completed', 'invisible_watermark_status' => 'unsupported',
    ]);
    $document = Document::create([
        'title' => $title, 'file_media_id' => $media->id, 'status' => $status,
        'published_at' => $status === 'published' ? now()->subMinute() : null,
        'download_count' => 0,
    ]);

    return [$document, $media, $bytes];
}

test('published managed document can be listed and downloaded with validated bytes', function () {
    [$document, , $bytes] = managedDocumentFixture('Test Document');
    $this->get('/dokumen')->assertOk()->assertSee($document->title);

    $response = $this->get(route('documents.download', $document->slug))
        ->assertOk()->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('Content-Disposition'))->toContain('test-document.pdf');
    expect(file_get_contents($response->baseResponse->getFile()->getPathname()))->toBe($bytes);
    expect($document->fresh()->download_count)->toBe(1);
});

test('unpublished managed document is absent from listing and download', function () {
    [$document] = managedDocumentFixture('Hidden Document', 'archived');

    $this->get('/dokumen')->assertOk()->assertDontSee($document->title);
    $this->get(route('documents.download', $document->slug))->assertNotFound();
});

test('modified managed document bytes cannot be downloaded', function () {
    [$document, $media] = managedDocumentFixture('Tampered Document');
    Storage::disk('local')->append('originals/'.$media->filename, 'tampered');

    $this->get(route('documents.download', $document->slug))->assertNotFound();
    expect($document->fresh()->download_count)->toBe(0);
});

test('successful repeated document downloads increment the count', function () {
    [$document] = managedDocumentFixture('Repeated Document');
    $url = route('documents.download', $document->slug);
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $this->get($url)->assertOk();
    }

    expect($document->fresh()->download_count)->toBe(3);
});
