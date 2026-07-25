<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PruneOrphanedImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $directories = ['covers', 'avatars'];

    public function __construct(protected int $gracePeriodHours = 24) {}

    public function handle(): void
    {
        $cutoffTime = Carbon::now()->subHours($this->gracePeriodHours);

        $totalScanned = 0;
        $totalDeleted = 0;

        foreach ($this->directories as $directory) {
            $this->processDirectory($directory, $cutoffTime, $totalScanned, $totalDeleted);
        }

        Log::info('S3 Prune Orphaned Images Summary', [
            'scanned_objects' => $totalScanned,
            'deleted_orphans' => $totalDeleted,
            'grace_period_hours' => $this->gracePeriodHours,
        ]);
    }

    protected function processDirectory(string $directory, Carbon $cutoffTime, int &$totalScanned, int &$totalDeleted): void
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('s3');
        $contents = $disk->listContents($directory, false);

        $batch = [];

        foreach ($contents as $object) {
            if ($object->isDir()) {
                continue;
            }

            $totalScanned++;

            $lastModified = Carbon::createFromTimestamp($object->lastModified());
            if ($lastModified->greaterThan($cutoffTime)) {
                continue;
            }

            $batch[] = $object->path();

            if (count($batch) >= 100) {
                $totalDeleted += $this->pruneOrphansInBatch($batch, $directory);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            $totalDeleted += $this->pruneOrphansInBatch($batch, $directory);
        }
    }

    protected function pruneOrphansInBatch(array $fileKeys, string $directory): int
    {
        $referencedKeys = $this->getReferencedKeysFromDatabase($fileKeys, $directory);

        $orphanedKeys = array_diff($fileKeys, $referencedKeys);

        if (empty($orphanedKeys)) {
            return 0;
        }

        Storage::disk('s3')->delete($orphanedKeys);

        return count($orphanedKeys);
    }

    protected function getReferencedKeysFromDatabase(array $fileKeys, string $directory): array
    {
        if ($directory === 'covers') {
            return DB::table('articles')
                ->whereIn('cover_image', $fileKeys)
                ->pluck('cover_image')
                ->toArray();
        }

        if ($directory === 'avatars') {
            return DB::table('users')
                ->whereIn('avatar', $fileKeys)
                ->pluck('avatar')
                ->toArray();
        }

        return [];
    }
}
