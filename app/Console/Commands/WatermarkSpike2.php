<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WatermarkSpike2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spike:watermark2';

    protected $description = 'Run the second watermark technical spike for EXIF/XMP and PDF robustness';

    public function handle()
    {
        $this->info('Starting Watermark Technical Spike 2...');
        @mkdir(storage_path('app/spike2'));

        $payload = 'VWCM-KERTAJAYA-ID-'.uniqid();
        $this->info("Payload: {$payload}");

        // --- Image Testing ---
        $this->info("\n--- Testing Image Structured Identifier (EOF / Chunk) ---");
        $imgPath = storage_path('app/spike2/base.jpg');
        $img = imagecreatetruecolor(200, 200);
        imagejpeg($img, $imgPath, 90);
        imagedestroy($img);

        // Inject identifier (EOF is equivalent to custom chunk that GD ignores)
        file_put_contents($imgPath, "<!-- EXIF_MOCK: {$payload} -->", FILE_APPEND);
        $this->info('Identifier injected into JPEG.');

        // Read it back
        $content = file_get_contents($imgPath);
        if (str_contains($content, $payload)) {
            $this->info('Baseline extraction: SUCCESS');
        }

        // Third-party re-save simulation
        $reprocessedPath = storage_path('app/spike2/reprocessed.jpg');
        $imgRe = imagecreatefromjpeg($imgPath);
        imagejpeg($imgRe, $reprocessedPath, 85);
        imagedestroy($imgRe);

        $reContent = file_get_contents($reprocessedPath);
        if (! str_contains($reContent, $payload)) {
            $this->info('After third-party re-save (JPEG): FAILED (Identifier stripped)');
        } else {
            $this->info('After third-party re-save (JPEG): SUCCESS');
        }

        // --- PDF Testing ---
        $this->info("\n--- Testing PDF Structured Metadata ---");
        $pdfPath = storage_path('app/spike2/dummy.pdf');
        // Standard minimal PDF
        $fakePdfContent = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\nxref\n0 3\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \ntrailer\n<< /Size 3 /Root 1 0 R >>\nstartxref\n111\n%%EOF\n";

        // Inject EOF metadata
        $pdfWatermarked = $fakePdfContent."\n%Watermark: {$payload}\n";
        $pdfWPath = storage_path('app/spike2/watermarked.pdf');
        file_put_contents($pdfWPath, $pdfWatermarked);
        $this->info('Identifier injected into PDF.');

        if (str_contains(file_get_contents($pdfWPath), $payload)) {
            $this->info('Baseline extraction: SUCCESS');
        }

        // Third-party re-save simulation (simulated by parsing and rebuilding PDF, or just stripping EOF beyond %%EOF)
        // Since we don't have a full PDF engine here, we simulate a tool that rebuilds xref and strips extraneous EOF data.
        $reprocessedPdfPath = storage_path('app/spike2/reprocessed.pdf');
        // Simulate a "clean" save by removing anything after %%EOF
        $cleanPdf = preg_replace('/(%%EOF\n?).*/is', '$1', $pdfWatermarked);
        file_put_contents($reprocessedPdfPath, $cleanPdf);

        if (! str_contains(file_get_contents($reprocessedPdfPath), $payload)) {
            $this->info('After third-party re-save (PDF flattened/rebuilt): FAILED (Identifier stripped)');
        } else {
            $this->info('After third-party re-save (PDF flattened/rebuilt): SUCCESS');
        }
    }
}
