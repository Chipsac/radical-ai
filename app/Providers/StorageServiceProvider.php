<?php

namespace App\Providers;

use Google\Cloud\Storage\StorageClient;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;
use League\Flysystem\GoogleCloudStorage\UniformBucketLevelAccessVisibility;

/**
 * Registers a "gcs" filesystem driver backed by Google Cloud Storage.
 *
 * Credentials come from Application Default Credentials, which on Cloud Run
 * means the revision's attached service account — nothing to configure and no
 * key file to leak.
 */
class StorageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Storage::extend('gcs', function ($app, array $config) {
            $client = new StorageClient(array_filter([
                'projectId' => $config['project_id'] ?? null,
            ]));

            $bucket = $client->bucket($config['bucket']);

            // The uploads bucket uses uniform bucket-level access, so per-object
            // ACLs are disabled at the bucket. The adapter's default visibility
            // handler sets a predefined ACL on every write, which such a bucket
            // rejects — every upload failed with UnableToWriteFile. This handler
            // makes visibility a no-op and leaves access to IAM, which is where
            // it is actually controlled.
            $adapter = new GoogleCloudStorageAdapter(
                $bucket,
                $config['path_prefix'] ?? '',
                new UniformBucketLevelAccessVisibility
            );

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
