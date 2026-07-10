<?php

namespace App\Services;

use Aws\S3\PostObjectV4;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class S3StorageService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function generatePresignedUrl(string $prefix, int $maxSize, string $fileName, string $contentType): array
    {

        $fileKey = $prefix.'/'.Str::uuid().'-'.pathinfo($fileName, PATHINFO_EXTENSION);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('s3');
        /** @phpstan-ignore-next-line */
        $client = $disk->getClient();
        $bucket = config('filesystems.disks.s3.bucket');

        $fields = [
            'key' => $fileKey,
            'Content-Type' => $contentType,
        ];

        $options = [
            ['bucket' => $bucket],
            ['key' => $fileKey],
            ['content-type' => $contentType],
            ['content-length-range', 0, $maxSize],
        ];

        $postObject = new PostObjectV4($client, $bucket, $fields, $options, '+5 minutes');

        $uploadUrl = $postObject->getFormAttributes()['action'] ?? '';
        $formFields = $postObject->getFormInputs();

        return [
            'upload_url' => $uploadUrl,
            'form_fields' => $formFields,
            'file_key' => $fileKey,
        ];
    }
}
