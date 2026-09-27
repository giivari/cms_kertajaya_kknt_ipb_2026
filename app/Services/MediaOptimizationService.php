<?php

namespace App\Services;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class MediaOptimizationService
{
    public function optimize(string $candidatePath, string $mime): void
    {
        $width = max(100, min(3840, (int) SettingsService::get('optimized_image_width', 1920)));
        $height = max(100, min(2160, (int) SettingsService::get('optimized_image_height', 1080)));
        $this->resizeCandidate($candidatePath, $candidatePath.'.optimized.'.pathinfo($candidatePath, PATHINFO_EXTENSION), $mime, $width, $height);
    }

    public function thumbnail(string $sourcePath, string $thumbnailPath, string $mime): void
    {
        $this->resizeCandidate($sourcePath, $thumbnailPath, $mime, 480, 270, forceWrite: true);
    }

    private function resizeCandidate(string $source, string $temporary, string $mime, int $width, int $height, bool $forceWrite = false): void
    {
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new \RuntimeException('Unsupported optimization format.');
        }

        $image = (new ImageManager(Driver::class))->decode($source);
        $wasOversized = $image->width() > $width || $image->height() > $height;
        $image->scaleDown(width: $width, height: $height);
        if ($mime === 'image/png') {
            $image->save($temporary);
        } else {
            $image->save($temporary, quality: 85);
        }
        if (! is_file($temporary) || filesize($temporary) <= 0) {
            throw new \RuntimeException('Image optimization did not produce a readable candidate.');
        }

        if ($forceWrite) {
            return;
        }

        if ($wasOversized || filesize($temporary) < filesize($source)) {
            if (! rename($temporary, $source)) {
                throw new \RuntimeException('Image optimization could not replace the private candidate.');
            }
        } elseif ($temporary !== $source) {
            unlink($temporary);
        }
    }
}
