<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class WatermarkSpike extends Command
{
    protected $signature = 'spike:watermark';

    protected $description = 'Run the watermark technical spike';

    public function handle()
    {
        $this->info('Starting Watermark Technical Spike...');
        @mkdir(storage_path('app/spike'));

        $manager = new ImageManager(new Driver);
        $payload = 'VWCM-KERTAJAYA-'.uniqid();
        $this->info("Original Payload: {$payload}");

        // 1. Create a dummy image using native GD
        $img = imagecreatetruecolor(500, 500);
        $bgColor = imagecolorallocate($img, 255, 0, 0);
        imagefill($img, 0, 0, $bgColor);
        for ($i = 0; $i < 100; $i++) {
            $col = imagecolorallocate($img, rand(0, 255), rand(0, 255), rand(0, 255));
            imagefilledellipse($img, rand(0, 500), rand(0, 500), rand(10, 50), rand(10, 50), $col);
        }
        $originalPath = storage_path('app/spike/original.png');
        imagepng($img, $originalPath);
        imagedestroy($img);

        // --- LSB Steganography ---
        $this->info("\n--- Testing LSB Steganography ---");
        $lsbPath = storage_path('app/spike/lsb.png');
        // Let's implement a rudimentary LSB insertion
        $imgLsb = imagecreatefrompng($originalPath);
        $width = imagesx($imgLsb);
        $height = imagesy($imgLsb);

        // Convert payload to binary string
        $binaryPayload = '';
        for ($i = 0; $i < strlen($payload); $i++) {
            $binaryPayload .= str_pad(decbin(ord($payload[$i])), 8, '0', STR_PAD_LEFT);
        }
        $binaryPayload .= '00000000'; // null terminator

        $payloadIndex = 0;
        $payloadLength = strlen($binaryPayload);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($payloadIndex < $payloadLength) {
                    $rgb = imagecolorat($imgLsb, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    // modify LSB of red channel
                    $bit = (int) $binaryPayload[$payloadIndex];
                    $r = ($r & 0xFE) | $bit;

                    $color = imagecolorallocate($imgLsb, $r, $g, $b);
                    imagesetpixel($imgLsb, $x, $y, $color);
                    $payloadIndex++;
                }
            }
        }
        imagepng($imgLsb, $lsbPath);
        imagedestroy($imgLsb);
        $this->info('LSB watermark applied.');

        // Read LSB
        $this->info('Extracted from original LSB: '.$this->readLsb($lsbPath));

        // Test Transformations on LSB
        $this->testTransformations($lsbPath, 'lsb');

        // --- Metadata Extraction/Insertion ---
        // GD doesn't easily write EXIF. Intervention v3 can read/write EXIF if using Imagick.
        // We'll simulate metadata by appending to the end of the file.
        $this->info("\n--- Testing Metadata (Appended to EOF) ---");
        $metaPath = storage_path('app/spike/meta.jpg');
        // Native GD to read png and save as jpg
        $imgMetaGd = imagecreatefrompng($originalPath);
        imagejpeg($imgMetaGd, $metaPath, 100);
        imagedestroy($imgMetaGd);

        $metaString = "<!-- WATERMARK: {$payload} -->";
        file_put_contents($metaPath, $metaString, FILE_APPEND);
        $this->info('Metadata appended.');
        $this->info('Extracted from original Meta: '.$this->readMeta($metaPath));

        $this->testTransformations($metaPath, 'meta');

        // --- PDF Spike ---
        $this->info("\n--- Testing PDF Metadata ---");
        $pdfPath = storage_path('app/spike/dummy.pdf');
        // Create a fake PDF
        $fakePdfContent = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n%%EOF\n";
        file_put_contents($pdfPath, $fakePdfContent);

        // 1. Hidden Text Layer (just appending a string)
        $pdfWatermarked = $fakePdfContent."\n%Watermark: {$payload}\n";
        $pdfWPath = storage_path('app/spike/watermarked.pdf');
        file_put_contents($pdfWPath, $pdfWatermarked);
        $this->info('PDF Watermark applied.');

        if (strpos(file_get_contents($pdfWPath), $payload) !== false) {
            $this->info('PDF Watermark extracted successfully.');
        } else {
            $this->error('PDF Watermark extraction failed.');
        }
    }

    private function testTransformations($path, $type)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $img = @imagecreatefromjpeg($path);
        } elseif ($ext === 'webp') {
            $img = @imagecreatefromwebp($path);
        } else {
            $img = @imagecreatefrompng($path);
        }

        // 1. JPEG Compression (75)
        $jpgPath = storage_path("app/spike/{$type}_compressed.jpg");
        imagejpeg($img, $jpgPath, 75);
        $ext1 = $type === 'lsb' ? $this->readLsb($jpgPath) : $this->readMeta($jpgPath);
        $this->info('After JPEG compression (75): '.($ext1 ? $ext1 : 'FAILED'));

        // 2. WebP Conversion
        $webpPath = storage_path("app/spike/{$type}_converted.webp");
        imagewebp($img, $webpPath);
        $ext2 = $type === 'lsb' ? $this->readLsb($webpPath) : $this->readMeta($webpPath);
        $this->info('After WebP conversion: '.($ext2 ? $ext2 : 'FAILED'));

        // 3. Resize (50%)
        $resizePath = storage_path("app/spike/{$type}_resized.png");
        $width = imagesx($img);
        $height = imagesy($img);
        $img2 = imagecreatetruecolor((int) ($width / 2), (int) ($height / 2));
        imagecopyresampled($img2, $img, 0, 0, 0, 0, (int) ($width / 2), (int) ($height / 2), $width, $height);
        imagepng($img2, $resizePath);
        $ext3 = $type === 'lsb' ? $this->readLsb($resizePath) : $this->readMeta($resizePath);
        $this->info('After Resize (50%): '.($ext3 ? $ext3 : 'FAILED'));
        imagedestroy($img2);

        // 4. Crop (10%)
        $cropPath = storage_path("app/spike/{$type}_cropped.png");
        $img3 = imagecrop($img, ['x' => 50, 'y' => 50, 'width' => $width - 100, 'height' => $height - 100]);
        imagepng($img3, $cropPath);
        $ext4 = $type === 'lsb' ? $this->readLsb($cropPath) : $this->readMeta($cropPath);
        $this->info('After Crop (10%): '.($ext4 ? $ext4 : 'FAILED'));
        imagedestroy($img3);
        imagedestroy($img);
    }

    private function readLsb($path)
    {
        // Simple LSB read
        if (! file_exists($path)) {
            return null;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $img = @imagecreatefromjpeg($path);
        } elseif ($ext === 'webp') {
            $img = @imagecreatefromwebp($path);
        } else {
            $img = @imagecreatefrompng($path);
        }

        if (! $img) {
            return null;
        }

        $width = imagesx($img);
        $height = imagesy($img);

        $binaryString = '';
        $charCount = 0;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($img, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $binaryString .= ($r & 1);

                if (strlen($binaryString) % 8 === 0) {
                    $char = chr(bindec(substr($binaryString, -8)));
                    if ($char === "\0") {
                        imagedestroy($img);

                        return substr(
                            implode('', array_map(function ($b) {
                                return chr(bindec($b));
                            }, str_split(substr($binaryString, 0, -8), 8))),
                            0, 50 // Limit length to avoid gibberish if it failed
                        );
                    }
                    $charCount++;
                    if ($charCount > 100) { // Limit max search
                        imagedestroy($img);

                        return 'FAILED/TOO_LONG';
                    }
                }
            }
        }
        imagedestroy($img);

        return 'NOT_FOUND';
    }

    private function readMeta($path)
    {
        if (! file_exists($path)) {
            return null;
        }
        $content = file_get_contents($path);
        if (preg_match('/<!-- WATERMARK: (.*?) -->/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
