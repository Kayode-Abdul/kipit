<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sync / Provision Batch Size
    |--------------------------------------------------------------------------
    |
    | Maximum records processed per HTTP payload or DB chunk. Keeps memory and
    | request size bounded when tenants grow past 150k–200k+ rows. Pulls are
    | cursor-paginated and can resume across scheduled sync runs.
    |
    */

    'batch_size' => (int) env('SYNC_BATCH_SIZE', 500),

    /*
    |--------------------------------------------------------------------------
    | Sync HTTP Timeout (seconds)
    |--------------------------------------------------------------------------
    */

    'http_timeout' => (int) env('SYNC_HTTP_TIMEOUT', 90),

    /*
    |--------------------------------------------------------------------------
    | Max Pull Batches Per Sync Run
    |--------------------------------------------------------------------------
    |
    | Caps work per sync:run so a 200k+ backlog can resume on the next schedule
    | tick instead of timing out one long job. 0 = unlimited (until has_more=false).
    |
    */

    'max_pull_batches_per_run' => (int) env('SYNC_MAX_PULL_BATCHES_PER_RUN', 100),

];
