<?php
// this configuration is needed in order to prepare gcs based file storage.
return array_merge(config('filesystems.disks'), ['gcs' => [
    'driver' => 'gcs',
    'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', null),
    'key_file' => env('GOOGLE_CLOUD_KEY_FILE', null), // optional: /path/to/service-account.json
    'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET', null),
    'path_prefix' => env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX', null), // optional: /default/path/to/apply/in/bucket
    'storage_api_uri' => env('GOOGLE_CLOUD_STORAGE_API_URI', null), // see: Public URLs below
], 's3' => [
    'driver' => 's3',
    'key' => env('AWS_S3_KEY', null),
    'secret' => env('AWS_S3_SECRET', null),
    'region' => env('AWS_S3_REGION', null),
    'bucket' => env('AWS_S3_BUCKET', null),
    'url' => env('AWS_S3_URL', null),
]]);
