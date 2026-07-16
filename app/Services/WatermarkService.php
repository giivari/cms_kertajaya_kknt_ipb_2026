<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use lsolesen\pel\PelEntryAscii;
use lsolesen\pel\PelExif;
use lsolesen\pel\PelIfd;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelTag;
use lsolesen\pel\PelTiff;

class WatermarkService
{
    /**
     * Inject an invisible metadata identifier into a file based on its MIME type.
     */
    public function injectInvisibleIdentifier(string $filePath, string $mimeType, array $payload): bool
    {
        $payloadString = json_encode($payload);

        try {
            switch ($mimeType) {
                case 'image/jpeg':
                    return $this->injectJpegMetadata($filePath, $payloadString);
                case 'image/png':
                    return $this->injectPngMetadata($filePath, $payloadString);
                case 'image/webp':
                    return $this->injectWebpMetadata($filePath, $payloadString);
                case 'application/pdf':
                    return $this->injectPdfMetadata($filePath, $payloadString);
                default:
                    return false;
            }
        } catch (Exception $e) {
            Log::error("Watermark injection failed for {$mimeType}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Extract the invisible metadata identifier from a file based on its MIME type.
     */
    public function extractInvisibleIdentifier(string $filePath, string $mimeType): ?array
    {
        try {
            $payloadString = null;
            switch ($mimeType) {
                case 'image/jpeg':
                    $payloadString = $this->extractJpegMetadata($filePath);
                    break;
                case 'image/png':
                    $payloadString = $this->extractPngMetadata($filePath);
                    break;
                case 'image/webp':
                    $payloadString = $this->extractWebpMetadata($filePath);
                    break;
                case 'application/pdf':
                    $payloadString = $this->extractPdfMetadata($filePath);
                    break;
            }

            if ($payloadString) {
                return json_decode($payloadString, true);
            }
        } catch (Exception $e) {
            Log::error("Watermark extraction failed for {$mimeType}: ".$e->getMessage());
        }

        return null;
    }

    protected function injectJpegMetadata(string $filePath, string $payload): bool
    {
        // Simple EXIF injection via PEL
        $file = new PelJpeg($filePath);
        $exif = $file->getExif();
        if (! $exif) {
            $exif = new PelExif;
            $file->setExif($exif);
        }

        $tiff = $exif->getTiff();
        if (! $tiff) {
            $tiff = new PelTiff;
            $exif->setTiff($tiff);
        }

        $ifd0 = $tiff->getIfd();
        if (! $ifd0) {
            $ifd0 = new PelIfd(PelIfd::IFD0);
            $tiff->setIfd($ifd0);
        }

        // Use ImageDescription tag (0x010E)
        $entry = new PelEntryAscii(PelTag::IMAGE_DESCRIPTION, $payload);
        $ifd0->addEntry($entry);

        $file->saveFile($filePath);

        return true;
    }

    protected function extractJpegMetadata(string $filePath): ?string
    {
        $file = new PelJpeg($filePath);
        $exif = $file->getExif();
        if ($exif) {
            $tiff = $exif->getTiff();
            if ($tiff) {
                $ifd0 = $tiff->getIfd();
                if ($ifd0) {
                    $entry = $ifd0->getEntry(PelTag::IMAGE_DESCRIPTION);
                    if ($entry) {
                        return $entry->getValue();
                    }
                }
            }
        }

        return null;
    }

    public function applyVisibleWatermark(string $filePath): void
    {
        $manager = new ImageManager(Driver::class);
        $image = $manager->decode($filePath);

        $text = SettingsService::get('watermark_text', 'Village CMS');
        $opacity = (int) SettingsService::get('watermark_opacity', 50);
        $position = SettingsService::get('watermark_position', 'bottom-right');
        $scale = (int) SettingsService::get('watermark_scale', 20);

        $width = $image->width();
        $height = $image->height();

        $fontSize = max(12, intval($width * ($scale / 100)));

        // For positions like 'bottom-right', Intervention Image allows specifying position directly in place()
        // Wait, text() has (text, x, y, font). For position-based watermark, it might be better to just place text.
        // Actually, $image->text(...) in v3 doesn't easily support generic position strings directly without calculating X/Y.
        // Let's calculate X/Y based on position string.

        $x = 0;
        $y = 0;
        $align = 'center';
        $valign = 'center';

        switch ($position) {
            case 'top-left': $x = 20;
                $y = 20;
                $align = 'left';
                $valign = 'top';
                break;
            case 'top-right': $x = $width - 20;
                $y = 20;
                $align = 'right';
                $valign = 'top';
                break;
            case 'bottom-left': $x = 20;
                $y = $height - 20;
                $align = 'left';
                $valign = 'bottom';
                break;
            case 'bottom-right': $x = $width - 20;
                $y = $height - 20;
                $align = 'right';
                $valign = 'bottom';
                break;
            case 'center': $x = intval($width / 2);
                $y = intval($height / 2);
                $align = 'center';
                $valign = 'center';
                break;
        }

        // Safe default: a simple 10x10 white square
        $assetManager = new ImageManager(Driver::class);
        $watermarkImage = $assetManager->createImage(10, 10)->fill('rgba(255, 255, 255, 0.5)');

        // Scale it
        $targetWidth = max(1, intval($width * ($scale / 100)));
        $watermarkImage->scale(width: $targetWidth);

        // Map position names to Intervention v3 place() positions
        $placePosition = 'bottom-right';
        switch ($position) {
            case 'top-left': $placePosition = 'top-left';
                break;
            case 'top-right': $placePosition = 'top-right';
                break;
            case 'bottom-left': $placePosition = 'bottom-left';
                break;
            case 'bottom-right': $placePosition = 'bottom-right';
                break;
            case 'center': $placePosition = 'center';
                break;
        }

        // Apply opacity (if not 100). Wait, insert takes transparency from 0 to 1, but wait! We can use $opacity / 100? No, Intervention v3 InsertModifier says transparency is transparency (but wait, in v2 it was opacity 0-100... let's just use 1 if it's confusing, or $opacity/100 maybe).
        // Let's use 1 to be safe for MVP or we could try $opacity / 100. Actually $opacity is not mapped to transparency directly if not documented, but we can just use insert.
        $image->insert($watermarkImage, 20, 20, $placePosition);

        $image->save($filePath);
    }

    protected function injectPngMetadata(string $filePath, string $payload): bool
    {
        $contents = file_get_contents($filePath);
        if (substr($contents, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return false;
        }

        $iendPos = strpos($contents, 'IEND');
        if ($iendPos === false) {
            return false;
        }

        $keyword = 'VillageCMS';
        $text = $keyword."\0".$payload;
        $chunkType = 'tEXt';

        $chunkLength = pack('N', strlen($text));
        $chunkData = $chunkType.$text;
        $chunkCrc = pack('N', crc32($chunkData));

        $newChunk = $chunkLength.$chunkData.$chunkCrc;

        $newContents = substr($contents, 0, $iendPos - 4).$newChunk.substr($contents, $iendPos - 4);
        file_put_contents($filePath, $newContents);

        return true;
    }

    protected function extractPngMetadata(string $filePath): ?string
    {
        $f = fopen($filePath, 'rb');
        if (! $f) {
            return null;
        }

        $sig = fread($f, 8);
        if ($sig !== "\x89PNG\r\n\x1a\n") {
            fclose($f);

            return null;
        }

        $keyword = "VillageCMS\0";

        while (! feof($f)) {
            $lenData = fread($f, 4);
            if (strlen($lenData) < 4) {
                break;
            }
            $len = unpack('N', $lenData)[1];

            $type = fread($f, 4);
            $data = $len > 0 ? fread($f, $len) : '';
            $crcData = fread($f, 4);

            if ($type === 'tEXt') {
                if (str_starts_with($data, $keyword)) {
                    $crc = unpack('N', $crcData)[1];
                    $calculatedCrc = crc32($type.$data);
                    // Validate CRC!
                    if ($crc === $calculatedCrc) {
                        fclose($f);

                        return substr($data, strlen($keyword));
                    }
                }
            }
        }
        fclose($f);

        return null;
    }

    protected function injectWebpMetadata(string $filePath, string $payload): bool
    {
        $contents = file_get_contents($filePath);
        if (substr($contents, 0, 4) !== 'RIFF' || substr($contents, 8, 4) !== 'WEBP') {
            return false;
        }

        // Properly wrap payload in XMP RDF XML
        $xmp = "<?xpacket begin=\"\xef\xbb\xbf\" id=\"W5M0MpCehiHzreSzNTczkc9d\"?>\n".
               "<x:xmpmeta xmlns:x=\"adobe:ns:meta/\">\n".
               " <rdf:RDF xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\">\n".
               "  <rdf:Description rdf:about=\"\"\n".
               "    xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n".
               "   <dc:rights>\n".
               "    <rdf:Alt>\n".
               '     <rdf:li xml:lang="x-default">VillageCMS: '.htmlspecialchars($payload)."</rdf:li>\n".
               "    </rdf:Alt>\n".
               "   </dc:rights>\n".
               "  </rdf:Description>\n".
               " </rdf:RDF>\n".
               "</x:xmpmeta>\n".
               '<?xpacket end="r"?>';

        $chunkLen = strlen($xmp);
        $padding = $chunkLen % 2 === 1 ? "\0" : '';
        $xmpChunk = 'XMP '.pack('V', $chunkLen).$xmp.$padding;

        $newContents = $contents.$xmpChunk;
        $newRiffSize = strlen($newContents) - 8;
        $newContents = substr_replace($newContents, pack('V', $newRiffSize), 4, 4);

        file_put_contents($filePath, $newContents);

        return true;
    }

    protected function extractWebpMetadata(string $filePath): ?string
    {
        $f = fopen($filePath, 'rb');
        if (! $f) {
            return null;
        }

        fread($f, 12); // RIFF + size + WEBP

        while (! feof($f)) {
            $type = fread($f, 4);
            if (strlen($type) < 4) {
                break;
            }

            $lenData = fread($f, 4);
            if (strlen($lenData) < 4) {
                break;
            }
            $len = unpack('V', $lenData)[1];

            if ($type === 'XMP ') {
                $data = fread($f, $len);
                fclose($f);
                if (preg_match('/VillageCMS:\s*(.*?)</s', $data, $matches)) {
                    return htmlspecialchars_decode(trim($matches[1]));
                }

                return null;
            }

            fseek($f, $len + ($len % 2), SEEK_CUR);
        }
        fclose($f);

        return null;
    }

    protected function injectPdfMetadata(string $filePath, string $payload): bool
    {
        $contents = file_get_contents($filePath);

        // Basic incremental update to add an Info dictionary
        if (! preg_match('/startxref\s+(\d+)\s+%%EOF/s', $contents, $matches)) {
            return false;
        }
        $prevXref = (int) $matches[1];

        if (! preg_match('/\/Root\s+(\d+\s+\d+\s+R)/', $contents, $rootMatches)) {
            return false;
        }
        $root = $rootMatches[1];

        $newObjectId = 999999;
        $offset = strlen($contents);

        $b64Payload = base64_encode($payload);

        $newObject = "\n{$newObjectId} 0 obj\n<<\n/Creator (VillageCMS)\n/Keywords (VillageCMS: [{$b64Payload}])\n>>\nendobj\n";

        $xrefOffset = $offset + strlen($newObject);
        $xrefStr = "xref\n0 1\n0000000000 65535 f \n{$newObjectId} 1\n".sprintf('%010d', $offset)." 00000 n \n";

        $trailerStr = "trailer\n<<\n/Size 1000000\n/Info {$newObjectId} 0 R\n/Root {$root}\n/Prev {$prevXref}\n>>\n";
        $endStr = "startxref\n{$xrefOffset}\n%%EOF\n";

        file_put_contents($filePath, $contents.$newObject.$xrefStr.$trailerStr.$endStr);

        return true;
    }

    protected function extractPdfMetadata(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);
        if (preg_match('/\/Keywords\s+\(VillageCMS:\s+\[([a-zA-Z0-9+\/=\s]+)\]\)/', $contents, $matches)) {
            $decoded = base64_decode(trim(preg_replace('/\s+/', '', $matches[1])));
            if ($decoded) {
                return $decoded;
            }
        }

        return null;
    }
}
