<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Admin;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Models\Media;
use App\Models\Page;
use App\Services\DocumentDeliveryService;
use App\Services\DocumentFileAssignment;
use App\Services\DocumentFilePolicy;
use App\Services\MediaDeletionService;
use App\Services\PageBuilderService;
use App\Filament\Support\PreviewStateNormalizer;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Livewire\Livewire;
use ZipArchive;

class P3CManagedDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_public_document_delivery_uses_actual_pdf_bytes_publication_and_revocation(): void
    {
        [$document, $bytes] = $this->document('pdf', $this->pdfBytes());
        $url = route('documents.download', $document->slug);

        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame($bytes, file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition'));

        $document->update(['status' => 'archived']);
        $this->get($url)->assertNotFound();
        $document->update(['status' => 'published']);
        $this->get($url)->assertOk();
        $document->delete();
        $this->get($url)->assertNotFound();
    }

    public function test_draft_future_and_missing_or_modified_file_cannot_be_downloaded(): void
    {
        [$document] = $this->document('pdf', $this->pdfBytes());
        $url = route('documents.download', $document->slug);

        $document->update(['status' => 'draft']);
        $this->get($url)->assertNotFound();
        $document->update(['status' => 'published']);
        $document->update(['published_at' => now()->addDay()]);
        $this->get($url)->assertNotFound();
        $document->update(['published_at' => now()->subMinute()]);
        Storage::disk('local')->put('originals/'.$document->fileMedia->filename, 'tampered');
        $this->get($url)->assertNotFound();
        Storage::disk('local')->delete('originals/'.$document->fileMedia->filename);
        $this->get($url)->assertNotFound();
    }

    public function test_ooxml_word_and_excel_are_real_private_files_with_correct_download_types(): void
    {
        foreach (['docx', 'xlsx'] as $extension) {
            $path = 'originals/'.\Illuminate\Support\Str::uuid().'.'.$extension;
            $this->writeOoxml($path, $extension);
            $file = app(DocumentFilePolicy::class)->inspect(Storage::disk('local')->path($path), 'sample.'.$extension);
            $media = $this->media($path, $file, 'sample.'.$extension);
            $document = Document::create([
                'title' => 'Sample '.$extension, 'file_media_id' => $media->id,
                'status' => 'published', 'published_at' => now()->subMinute(),
            ]);
            $response = $this->get(route('documents.download', $document->slug))->assertOk()
                ->assertHeader('Content-Type', DocumentFilePolicy::MIMES[$extension]);
            $this->assertStringContainsString('.'.$extension, $response->headers->get('Content-Disposition'));
        }
    }

    public function test_renamed_image_generic_zip_and_corrupt_pdf_are_rejected(): void
    {
        $policy = app(DocumentFilePolicy::class);
        foreach ([
            'fake.pdf' => "\x89PNG\r\n\x1a\n",
            'fake.docx' => 'PKNOT-A-DOCUMENT',
            'fake.xlsx' => 'PKNOT-A-DOCUMENT',
            'broken.pdf' => '%PDF-1.4 broken',
            'fake.doc' => "\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1".str_repeat("\0", 1016),
            'fake.xls' => "\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1".str_repeat("\0", 1016),
        ] as $name => $bytes) {
            $path = 'originals/'.\Illuminate\Support\Str::uuid().'.'.pathinfo($name, PATHINFO_EXTENSION);
            Storage::disk('local')->put($path, $bytes);
            try {
                $policy->inspect(Storage::disk('local')->path($path), $name);
                $this->fail($name.' was accepted');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }

    public function test_uploaded_document_assignment_records_real_checksum_without_image_watermark_claim(): void
    {
        $path = 'originals/'.\Illuminate\Support\Str::uuid().'.pdf';
        $bytes = $this->pdfBytes();
        Storage::disk('local')->put($path, $bytes);

        $id = DB::transaction(fn () => app(DocumentFileAssignment::class)->assign([
            'document_upload' => $path, 'document_upload_name' => 'Laporan.pdf',
        ]));
        $media = Media::findOrFail($id);
        $this->assertSame(hash('sha256', $bytes), $media->checksum);
        $this->assertSame('unsupported', $media->invisible_watermark_status->value);
        $this->assertSame('local', $media->disk);
        $this->assertSame('originals', $media->directory);
    }

    public function test_active_filament_create_uploads_a_real_pdf_privately(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(Admin::factory()->create([
            'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
        ]));
        $bytes = $this->pdfBytes();
        Livewire::test(CreateDocument::class)->fillForm([
            'title' => 'Laporan Publik',
            'document_upload' => UploadedFile::fake()->createWithContent('laporan.pdf', $bytes),
        ])->call('create')->assertHasNoFormErrors();

        $document = Document::sole();
        $media = $document->fileMedia;
        $this->assertSame('local', $media->disk);
        $this->assertSame('originals', $media->directory);
        $this->assertSame('unsupported', $media->invisible_watermark_status->value);
        $this->assertSame($bytes, Storage::disk('local')->get('originals/'.$media->filename));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_active_filament_create_accepts_structurally_valid_ooxml_word_and_excel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(Admin::factory()->create([
            'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
        ]));
        foreach (['docx', 'xlsx'] as $extension) {
            $path = 'originals/'.\Illuminate\Support\Str::uuid().'.'.$extension;
            $this->writeOoxml($path, $extension);
            $bytes = Storage::disk('local')->get($path);
            $upload = UploadedFile::fake()->createWithContent('office.'.$extension, $bytes);
            $this->assertSame($extension, app(DocumentFilePolicy::class)->inspect($upload->getRealPath(), 'office.'.$extension)['extension']);
            $component = Livewire::test(CreateDocument::class)->fillForm([
                'title' => 'Office '.$extension,
                'document_upload' => $upload,
            ]);
            $raw = collect($component->instance()->form->getRawState()['document_upload'] ?? [])->first();
            $this->assertSame($bytes, file_get_contents($raw->getRealPath()), 'Livewire changed temporary file bytes');
            $this->assertSame('office.'.$extension, $raw->getClientOriginalName());
            $this->assertSame($extension, app(DocumentFilePolicy::class)->inspect($raw->getRealPath(), $raw->getClientOriginalName())['extension']);
            $component->call('create');
            $this->assertSame([], $component->errors()->all());
            $document = Document::where('title', 'Office '.$extension)->firstOrFail();
            $this->assertSame($extension, $document->fileMedia->extension);
            $this->assertSame('unsupported', $document->fileMedia->invisible_watermark_status->value);
            $this->assertSame($bytes, Storage::disk('local')->get('originals/'.$document->fileMedia->filename));
        }
    }

    public function test_admin_preview_remains_protected_and_can_read_a_private_draft(): void
    {
        [$document, $bytes] = $this->document('pdf', $this->pdfBytes());
        $document->update(['status' => 'draft']);
        $url = route('documents.preview', $document->slug);
        $this->get($url)->assertRedirect();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = Admin::factory()->create();
        $this->actingAs($admin)->get($url)->assertRedirect(Filament::getSetUpRequiredMultiFactorAuthenticationUrl());
        $admin->update(['app_authentication_secret' => AppAuthentication::make()->generateSecret()]);
        $response = $this->actingAs($admin)->get($url)->assertOk();
        $this->assertSame($bytes, file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_active_filament_edit_keeps_old_file_on_failure_then_switches_after_valid_replacement(): void
    {
        [$document, $oldBytes] = $this->document('pdf', $this->pdfBytes());
        $oldMedia = $document->fileMedia;
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(Admin::factory()->create([
            'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
        ]));

        Livewire::test(EditDocument::class, ['record' => $document->getRouteKey()])->fillForm([
            'document_upload' => UploadedFile::fake()->createWithContent('broken.pdf', "\x89PNG\r\n\x1a\n"),
        ])->call('save')->assertHasFormErrors(['document_upload']);
        $this->assertSame($oldMedia->id, $document->fresh()->file_media_id);
        $this->assertSame($oldBytes, Storage::disk('local')->get('originals/'.$oldMedia->filename));

        $newBytes = $this->pdfBytes();
        Livewire::test(EditDocument::class, ['record' => $document->getRouteKey()])->fillForm([
            'document_upload' => UploadedFile::fake()->createWithContent('replacement.pdf', $newBytes),
        ])->call('save')->assertHasNoFormErrors();
        $this->assertNotSame($oldMedia->id, $document->fresh()->file_media_id);
        Storage::disk('local')->assertExists('originals/'.$oldMedia->filename);
        $this->assertSame($newBytes, Storage::disk('local')->get('originals/'.$document->fresh()->fileMedia->filename));
    }

    public function test_failed_replacement_and_image_selection_leave_the_existing_document_available(): void
    {
        [$document] = $this->document('pdf', $this->pdfBytes());
        $originalId = $document->file_media_id;
        $badPath = 'originals/'.\Illuminate\Support\Str::uuid().'.pdf';
        Storage::disk('local')->put($badPath, "\x89PNG\r\n\x1a\n");

        try {
            DB::transaction(fn () => app(DocumentFileAssignment::class)->assign([
                'document_upload' => $badPath, 'document_upload_name' => 'wrong.pdf',
            ], $originalId));
            $this->fail('Corrupt replacement was accepted');
        } catch (ValidationException) {
            $this->assertSame($originalId, $document->fresh()->file_media_id);
            $this->get(route('documents.download', $document->slug))->assertOk();
        }

        $image = Media::factory()->create();
        $this->expectException(ValidationException::class);
        DB::transaction(fn () => app(DocumentFileAssignment::class)->assign(['file_media_id' => $image->id]));
    }

    public function test_builder_reference_blocks_permanent_document_deletion_and_media_deletion(): void
    {
        [$document] = $this->document('pdf', $this->pdfBytes());
        $media = $document->fileMedia;
        $page = Page::create(['title' => 'Reference page', 'status' => 'draft']);
        app(PageBuilderService::class)->saveSectionsAndComponents($page, [[
            'name' => 'Docs', 'components' => [[ 'type' => 'documents', 'data' => ['document_ids' => [$document->id]] ]],
        ]]);

        $this->expectException(ValidationException::class);
        $document->forceDelete();
    }

    public function test_legacy_builder_uses_only_a_unique_real_media_to_document_relationship(): void
    {
        [$document] = $this->document('pdf', $this->pdfBytes());
        $payload = ['documents' => [$document->file_media_id]];
        $html = view('pages.components.documents', ['data' => $payload])->render();
        $this->assertStringContainsString(route('documents.download', $document->slug), $html);
        $this->assertSame([$document->id], PreviewStateNormalizer::pageComponent('documents', ['document_ids' => [$document->id]])['document_ids']);

        $other = Document::create([
            'title' => 'Same attachment', 'file_media_id' => $document->file_media_id,
            'status' => 'published', 'published_at' => now()->subMinute(),
        ]);
        $this->assertStringNotContainsString(route('documents.download', $document->slug), view('pages.components.documents', ['data' => $payload])->render());
        $this->assertStringNotContainsString(route('documents.download', $other->slug), view('pages.components.documents', ['data' => $payload])->render());
        try {
            app(MediaDeletionService::class)->validateDeletion($document->fileMedia);
            $this->fail('Referenced file was deletable');
        } catch (\Exception $exception) {
            $this->assertStringContainsString('in use', $exception->getMessage());
        }
    }

    public function test_managed_builder_identity_survives_roundtrip_and_legacy_media_id_is_not_a_document_id(): void
    {
        [$document] = $this->document('pdf', $this->pdfBytes());
        $page = Page::create(['title' => 'Document page', 'status' => 'published', 'published_at' => now()->subMinute()]);
        $sections = [[
            'name' => 'Documents', 'layout_type' => 'single_column', 'is_visible' => true,
            'components' => [[ 'type' => 'documents', 'data' => ['document_ids' => [$document->id]] ]],
        ]];
        app(PageBuilderService::class)->saveSectionsAndComponents($page, $sections);
        $component = $page->sections()->firstOrFail()->components()->firstOrFail();
        $this->assertSame([$document->id], $component->content_data['document_ids']);
        $state = app(PageBuilderService::class)->reconstructBuilderState($page->fresh('sections.components'));
        app(PageBuilderService::class)->saveSectionsAndComponents($page, $state);
        $this->assertSame($component->id, $page->sections()->firstOrFail()->components()->firstOrFail()->id);
        $this->assertStringContainsString(route('documents.download', $document->slug), view('pages.components.documents', ['data' => $component->content_data])->render());

        $legacy = ['documents' => [$document->id + 1000]];
        $component->update(['content_data' => $legacy]);
        $this->assertStringNotContainsString(route('documents.download', $document->slug), view('pages.components.documents', ['data' => $legacy])->render());
        $roundtrip = app(PageBuilderService::class)->reconstructBuilderState($page->fresh('sections.components'));
        app(PageBuilderService::class)->saveSectionsAndComponents($page, $roundtrip);
        $this->assertSame($legacy['documents'], $component->fresh()->content_data['documents']);
    }

    private function document(string $extension, string $bytes): array
    {
        $path = 'originals/'.\Illuminate\Support\Str::uuid().'.'.$extension;
        Storage::disk('local')->put($path, $bytes);
        $file = app(DocumentFilePolicy::class)->inspect(Storage::disk('local')->path($path), 'sample.'.$extension);
        $media = $this->media($path, $file, 'sample.'.$extension);
        $document = Document::create([
            'title' => 'Sample '.$extension.' report', 'file_media_id' => $media->id,
            'status' => 'published', 'published_at' => now()->subMinute(),
        ]);

        return [$document, $bytes];
    }

    private function media(string $path, array $file, string $originalName): Media
    {
        return Media::create([
            'disk' => 'local', 'directory' => 'originals', 'filename' => basename($path),
            'original_filename' => $originalName, 'extension' => $file['extension'],
            'mime_type' => $file['mime'], 'size' => $file['size'], 'checksum' => $file['checksum'],
            'metadata' => ['document_validation' => ['version' => 1, 'format' => $file['extension']]],
            'processing_status' => 'completed', 'invisible_watermark_status' => 'unsupported',
        ]);
    }

    private function pdfBytes(): string
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

    private function writeOoxml(string $path, string $extension): void
    {
        $main = $extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
        $kind = $extension === 'docx' ? 'wordprocessingml.document.main+xml' : 'spreadsheetml.sheet.main+xml';
        $root = $extension === 'docx' ? 'document' : 'workbook';
        $namespace = $extension === 'docx'
            ? 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
            : 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        Storage::disk('local')->makeDirectory('originals');
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path($path), ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        $zip->addFromString('[Content_Types].xml', '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/'.$main.'" ContentType="application/vnd.openxmlformats-officedocument.'.$kind.'"/></Types>');
        $zip->addFromString('_rels/.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Target="'.$main.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"/></Relationships>');
        $content = $extension === 'docx' ? '<body><p/></body>' : '<sheets><sheet name="Sheet 1" sheetId="1" r:id="rId1"/></sheets>';
        $zip->addFromString($main, '<'.$root.' xmlns="'.$namespace.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'.$content.'</'.$root.'>');
        if ($extension === 'xlsx') {
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
            $zip->addFromString('xl/worksheets/sheet1.xml', '<worksheet xmlns="'.$namespace.'"><sheetData/></worksheet>');
        }
        $zip->close();
    }
}
