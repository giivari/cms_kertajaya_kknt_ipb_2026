<?php

namespace Tests\Feature;

use App\Contracts\MediaUsageResolver;
use App\Enums\MediaProcessingStatus;
use App\Models\Media;
use App\Services\MediaDeletionService;
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

        $resolver = new class implements MediaUsageResolver
        {
            public function isInUse(Media $media): bool
            {
                return true;
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

    public function test_atomic_bulk_delete_one_invalid_prevents_all()
    {
        $media1 = Media::factory()->create(['processing_status' => MediaProcessingStatus::COMPLETED]);
        $media2 = Media::factory()->create(['processing_status' => MediaProcessingStatus::COMPLETED]);
        $media3 = Media::factory()->create(['processing_status' => MediaProcessingStatus::PENDING]);

        $service = app(MediaDeletionService::class);
        $result = $service->bulkDelete(collect([$media1, $media2, $media3]));

        $this->assertEquals(0, $result['deleted']);
        $this->assertNotEmpty($result['errors']);

        // None should be deleted
        $this->assertNotSoftDeleted($media1);
        $this->assertNotSoftDeleted($media2);
        $this->assertNotSoftDeleted($media3);
    }

    public function test_atomic_bulk_delete_all_eligible_succeeds()
    {
        $media1 = Media::factory()->create(['processing_status' => MediaProcessingStatus::COMPLETED]);
        $media2 = Media::factory()->create(['processing_status' => MediaProcessingStatus::COMPLETED]);
        $media3 = Media::factory()->create(['processing_status' => MediaProcessingStatus::COMPLETED]);

        $service = app(MediaDeletionService::class);
        $result = $service->bulkDelete(collect([$media1, $media2, $media3]));

        $this->assertEquals(3, $result['deleted']);
        $this->assertEmpty($result['errors']);

        $this->assertSoftDeleted($media1);
        $this->assertSoftDeleted($media2);
        $this->assertSoftDeleted($media3);
    }

    public function test_atomic_bulk_delete_resolver_protected_aborts_all()
    {
        $media1 = Media::factory()->create(['processing_status' => MediaProcessingStatus::COMPLETED]);
        $media2 = Media::factory()->create(['processing_status' => MediaProcessingStatus::COMPLETED]);

        $usageService = app(MediaUsageService::class);
        $protectedId = $media2->id;

        $resolver = new class($protectedId) implements MediaUsageResolver
        {
            public function __construct(private $protectedId) {}

            public function isInUse(Media $media): bool
            {
                return $media->id === $this->protectedId;
            }

            public function getUsage(Media $media): array
            {
                return $media->id === $this->protectedId ? ['Page: Homepage'] : [];
            }
        };

        $usageService->registerResolver($resolver);

        $service = app(MediaDeletionService::class);
        $result = $service->bulkDelete(collect([$media1, $media2]));

        $this->assertEquals(0, $result['deleted']);
        $this->assertNotEmpty($result['errors']);

        // Neither should be deleted
        $this->assertNotSoftDeleted($media1);
        $this->assertNotSoftDeleted($media2);
    }
}
