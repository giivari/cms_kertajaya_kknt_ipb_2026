<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Smalot\PdfParser\Element;
use Smalot\PdfParser\Element\ElementName;
use Smalot\PdfParser\Header;
use Smalot\PdfParser\Parser;
use Smalot\PdfParser\PDFObject;
use SplObjectStorage;

final class MediaInputPolicy
{
    public const EXTENSIONS = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'image/heic' => ['heic'],
        'application/pdf' => ['pdf'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
    ];

    public const PUBLIC_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    public function maxKilobytes(): int
    {
        // Retain the uploader's 10 MB ceiling; respect a stricter configured limit.
        return max(1, min(10, (int) SettingsService::get('max_upload_size', 10))) * 1024;
    }

    public function originalPath(string $relativePath): string
    {
        if (! preg_match('~^originals/[^/\\\\]+$~D', $relativePath)) {
            $this->reject();
        }
        $root = realpath(Storage::disk('local')->path('originals'));
        $path = realpath(Storage::disk('local')->path($relativePath));
        if ($root === false || $path === false || dirname($path) !== $root) {
            $this->reject();
        }

        return $path;
    }

    public function inspect(string $path, ?string $clientFilename = null, bool $publication = false): string
    {
        if (! is_file($path) || ! is_readable($path) || filesize($path) <= 0) {
            $this->reject();
        }
        // Generated metadata may increase the output size; the input limit was
        // already checked before processing. Output must still be readable.
        if (! $publication && filesize($path) > $this->maxKilobytes() * 1024) {
            $this->reject('Berkas melebihi batas ukuran unggahan.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (! isset(self::EXTENSIONS[$mime]) || ($publication && ! in_array($mime, self::PUBLIC_MIMES, true))) {
            $this->reject('Format berkas tidak didukung untuk operasi ini.');
        }
        if ($clientFilename !== null && ! in_array(strtolower(pathinfo($clientFilename, PATHINFO_EXTENSION)), self::EXTENSIONS[$mime], true)) {
            $this->reject('Ekstensi berkas tidak sesuai dengan isi berkas.');
        }

        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $size = @getimagesize($path);
            $maxWidth = max(1, (int) SettingsService::get('max_image_width', 3840));
            $maxHeight = max(1, (int) SettingsService::get('max_image_height', 2160));
            if (! $size || $size[0] > $maxWidth || $size[1] > $maxHeight || ($size['mime'] ?? null) !== $mime) {
                $this->reject('Gambar tidak valid atau dimensinya melebihi batas.');
            }
            $image = @imagecreatefromstring(file_get_contents($path));
            if ($image === false) {
                $this->reject('Gambar tidak dapat dibaca.');
            }
            imagedestroy($image);
        } elseif ($mime === 'application/pdf') {
            try {
                $pdf = (new Parser())->parseFile($path);
                if ($pdf->getPages() === []) {
                    $this->reject();
                }
                $seen = new SplObjectStorage();
                foreach ($pdf->getObjects() as $object) {
                    $this->inspectPdfNode($object, $seen);
                }
            } catch (ValidationException $exception) {
                throw $exception;
            } catch (\Throwable $exception) {
                $this->reject('PDF tidak dapat diverifikasi. Gunakan PDF valid tanpa enkripsi.');
            }
        }

        // HEIC remains private until conversion and image validation succeed.
        // Office formats retain private upload support, never public publication.
        return $mime;
    }

    private function inspectPdfNode(mixed $node, SplObjectStorage $seen, int $depth = 0): void
    {
        if ($depth > 64) {
            $this->reject('Struktur PDF terlalu kompleks untuk diverifikasi.');
        }
        if (is_object($node)) {
            if ($seen->contains($node)) {
                return;
            }
            $seen->attach($node);
        }

        if ($node instanceof PDFObject) {
            $this->inspectPdfNode($node->getHeader(), $seen, $depth + 1);
        } elseif ($node instanceof Header) {
            foreach ($node->getElements() as $name => $value) {
                $this->checkPdfName($name);
                $this->inspectPdfNode($value, $seen, $depth + 1);
            }
        } elseif ($node instanceof ElementName) {
            $this->checkPdfName($node->getContent());
        } elseif ($node instanceof Element && is_array($node->getContent())) {
            foreach ($node->getContent() as $value) {
                $this->inspectPdfNode($value, $seen, $depth + 1);
            }
        }
    }

    private function checkPdfName(string $name): void
    {
        $name = preg_replace_callback('/#([0-9a-f]{2})/i', fn ($match) => chr(hexdec($match[1])), $name);
        if (in_array(strtolower($name), ['javascript', 'js', 'launch', 'embeddedfiles', 'embeddedfile', 'richmedia', 'xfa', 'submitform', 'importdata'], true)) {
            $this->reject('PDF mengandung elemen aktif atau lampiran yang tidak diizinkan.');
        }
    }

    private function reject(string $message = 'Berkas tidak valid atau tidak dapat diverifikasi.'): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
