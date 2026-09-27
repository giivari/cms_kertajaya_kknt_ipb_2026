<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\MediaDeletionService;
use Illuminate\Console\Command;

final class RetryMediaCleanup extends Command
{
    protected $signature = 'media:cleanup {media : Exact archived Media ID} {--execute : Remove tracked files and permanently delete the record}';

    protected $description = 'Inspect or explicitly retry cleanup for one archived media record';

    public function handle(MediaDeletionService $deletion): int
    {
        $id = filter_var($this->argument('media'), FILTER_VALIDATE_INT);
        $media = $id ? Media::withTrashed()->find($id) : null;
        if (! $media || ! $media->trashed()) {
            $this->error('The requested archived Media record was not found.');

            return self::FAILURE;
        }

        if (! $this->option('execute')) {
            $this->warn('DRY RUN ONLY. Add --execute to retry tracked-file cleanup and permanent deletion.');
            $this->table(['Media ID', 'Cleanup status', 'Last error code'], [[
                $media->id,
                $media->cleanup_status ?? 'not-started',
                $media->cleanup_error ?? '-',
            ]]);

            return self::SUCCESS;
        }

        $deletion->permanentlyDelete($media);
        $this->info('Tracked-file cleanup and permanent deletion completed.');

        return self::SUCCESS;
    }
}
