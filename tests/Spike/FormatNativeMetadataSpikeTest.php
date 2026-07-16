<?php

namespace Tests\Spike;

use App\Services\WatermarkService;
use Tests\TestCase;

class FormatNativeMetadataSpikeTest extends TestCase
{
    protected WatermarkService $service;

    protected function setUp(): void
    {
        parent::setUp();
        @mkdir(storage_path('app/spike'));
        $this->service = new WatermarkService;
    }

    public function test_jpeg_metadata_injection()
    {
        $image = imagecreatetruecolor(100, 100);
        $color = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $color);
        $baselinePath = storage_path('app/spike/baseline.jpg');
        imagejpeg($image, $baselinePath);
        imagedestroy($image);

        $payload = [
            'uuid' => '1234-5678',
            'signature' => 'signed_hash',
        ];

        $injectedPath = storage_path('app/spike/injected.jpg');
        copy($baselinePath, $injectedPath);

        $this->assertTrue($this->service->injectInvisibleIdentifier($injectedPath, 'image/jpeg', $payload));

        $extracted = $this->service->extractInvisibleIdentifier($injectedPath, 'image/jpeg');
        $this->assertEquals($payload, $extracted);

        // Expected failure after destructive transformation
        $resizedImage = imagecreatefromjpeg($injectedPath);
        $resizedPath = storage_path('app/spike/resized.jpg');
        imagejpeg($resizedImage, $resizedPath); // gd strip EXIF by default
        imagedestroy($resizedImage);

        $this->assertNull($this->service->extractInvisibleIdentifier($resizedPath, 'image/jpeg'));
    }

    public function test_png_metadata_injection()
    {
        $image = imagecreatetruecolor(100, 100);
        $baselinePath = storage_path('app/spike/baseline.png');
        imagepng($image, $baselinePath);
        imagedestroy($image);

        $payload = ['uuid' => 'abcd', 'signature' => '1234'];
        $injectedPath = storage_path('app/spike/injected.png');
        copy($baselinePath, $injectedPath);

        $this->assertTrue($this->service->injectInvisibleIdentifier($injectedPath, 'image/png', $payload));

        $extracted = $this->service->extractInvisibleIdentifier($injectedPath, 'image/png');
        $this->assertEquals($payload, $extracted);
    }

    public function test_webp_metadata_injection()
    {
        $image = imagecreatetruecolor(100, 100);
        $baselinePath = storage_path('app/spike/baseline.webp');
        imagewebp($image, $baselinePath);
        imagedestroy($image);

        $payload = ['uuid' => 'webp1', 'signature' => 'sig'];
        $injectedPath = storage_path('app/spike/injected.webp');
        copy($baselinePath, $injectedPath);

        $this->assertTrue($this->service->injectInvisibleIdentifier($injectedPath, 'image/webp', $payload));

        $extracted = $this->service->extractInvisibleIdentifier($injectedPath, 'image/webp');
        $this->assertEquals($payload, $extracted);
    }

    public function test_pdf_metadata_injection()
    {
        $payload = ['uuid' => 'pdf1', 'signature' => 'hash'];

        $pdfPath = storage_path('app/spike/baseline.pdf');
        $pdfContent = "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n>>\nendobj\n"
                    ."2 0 obj\n<<\n/Title (Test PDF)\n>>\nendobj\n"
                    ."xref\n0 3\n0000000000 65535 f \n0000000009 00000 n \n0000000050 00000 n \n"
                    ."trailer\n<<\n/Size 3\n/Root 1 0 R\n/Info 2 0 R\n>>\nstartxref\n100\n%%EOF\n";
        file_put_contents($pdfPath, $pdfContent);

        $injectedPath = storage_path('app/spike/injected.pdf');
        copy($pdfPath, $injectedPath);

        $this->assertTrue($this->service->injectInvisibleIdentifier($injectedPath, 'application/pdf', $payload));

        $extracted = $this->service->extractInvisibleIdentifier($injectedPath, 'application/pdf');
        $this->assertEquals($payload, $extracted);
    }
}
