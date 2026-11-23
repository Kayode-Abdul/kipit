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

    /*
    |--------------------------------------------------------------------------
    | Integrity Verify Interval (minutes)
    |--------------------------------------------------------------------------
    |
    | Full table fingerprints are expensive on large shops. On routine syncs,
    | skip verify unless this many minutes have passed (cloud-wins always runs).
    | Shared hosting / desktop both benefit — no Redis required.
    |
    */

    'integrity_verify_minutes' => (int) env('SYNC_INTEGRITY_VERIFY_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Schedule Sync On Server
    |--------------------------------------------------------------------------
    |
    | Desktop apps schedule sync locally. Keep false on shared hosting so
    | central cron does not queue useless sync:run jobs without a worker.
    |
    */

    'schedule_on_server' => (bool) env('SYNC_SCHEDULE_ON_SERVER', false),

];
