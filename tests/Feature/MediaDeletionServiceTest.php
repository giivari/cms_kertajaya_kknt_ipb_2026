<?php

namespace Tests\Feature;

use App\Contracts\MediaUsageResolver;
use App\Enums\MediaProcessingStatus;
use App\Models\Media;
use App\Services\MediaUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_safe_unused_deletion()
    {
        $media = Media::factory()->create([
            'processing_status' => MediaProcessingStatus::COMPLETED,
        ]);

        // This should not throw an exception
        $media->delete();
        $this->assertSoftDeleted($media);
    }

    public function test_processing_media_rejected()
    {
        $media = Media::factory()->create([
            'processing_status' => MediaProcessingStatus::PROCESSING,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete media that is currently pending or processing.');

        $media->delete();
    }

    public function test_pending_media_rejected()
    {
        $media = Media::factory()->create([
            'processing_status' => MediaProcessingStatus::PENDING,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete media that is currently pending or processing.');

        $media->delete();
    }

    public function test_resolver_protected_media_rejected()
    {
        $media = Media::factory()->create([
            'processing_status' => MediaProcessingStatus::COMPLETED,
        ]);

        $usageService = app(MediaUsageService::class);

        // Create an anonymous class implementing MediaUsageResolver
        $resolver = new class implements MediaUsageResolver
        {
            public function isInUse(Media $media): bool
            {
                return true; // Always in use
            }

            public function getUsage(Media $media): array
            {
                return ['Test Usage'];
            }
        };

        $usageService->registerResolver($resolver);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete media because it is currently in use by other content.');

        $media->delete();
    }

    public function test_bulk_delete_evaluates_every_record()
    {
        $media1 = Media::factory()->create(['processing_status' => MediaProcessingStatus::COMPLETED]);
        $media2 = Media::factory()->create(['processing_status' => MediaProcessingStatus::PENDING]); // Will fail

        $failed = false;
        try {
            // Manual loop like Filament would do (if not caught earlier)
            $media1->delete();
            $media2->delete();
        } catch (\Exception $e) {
            $failed = true;
        }

        $this->assertTrue($failed);
        $this->assertSoftDeleted($media1); // First one succeeded
        $this->assertNotSoftDeleted($media2); // Second one failed
    }
}
