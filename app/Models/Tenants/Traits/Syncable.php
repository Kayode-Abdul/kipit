<?php

namespace App\Models\Tenants\Traits;

use Illuminate\Support\Str;

trait Syncable
{
    public static function bootSyncable(): void
    {
        static::creating(function ($model) {
            if (empty($model->sync_id)) {
                $model->sync_id = Str::uuid()->toString();
            }
            $model->is_synced = false;
        });

        static::updating(function ($model) {
            // Only mark as unsynced if we are modifying it locally.
            // If the Sync Engine is merging data, it will explicitly set is_synced = true
            // which will make isDirty('is_synced') true, bypassing this override.
            if (!$model->isDirty('is_synced')) {
                $model->is_synced = false;
            }
        });
    }
}
