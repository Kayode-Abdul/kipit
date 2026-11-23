<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SyncController extends Controller
{
    protected array $foreignKeyMapping = [
        'category_id' => 'categories',
        'product_id' => 'products',
        'member_id' => 'members',
        'selling_id' => 'sellings',
        'user_id' => 'users',
        'supplier_id' => 'suppliers',
        'purchasing_id' => 'purchasings',
        'stock_opname_id' => 'stock_opnames',
        'debt_id' => 'debts',
        'payment_method_id' => 'payment_methods',
        'cash_drawer_id' => 'cash_drawers',
        'table_id' => 'tables',
        'price_unit_id' => 'price_units',
        'subscription_plan_id' => 'subscription_plans',
        'role_id' => 'roles',
        'permission_id' => 'permissions',
    ];

    protected array $tables = [
        'users', 'categories', 'products', 'members', 'sellings', 'selling_details',
        'stocks', 'suppliers', 'purchasings', 'stock_opnames', 'stock_opname_items',
        'receivables', 'receivable_items', 'receivable_payments', 'cash_drawers',
        'cart_items', 'abouts', 'settings', 'features', 'profiles', 'payment_methods',
        'vouchers', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions',
        'role_has_permissions',
    ];

    public function push(Request $request)
    {
        $started = microtime(true);
        $payload = $request->input('sync_data', []);
        $rowCount = 0;

        try {
            foreach ($payload as $tableName => $records) {
                if (empty($records) || ! Schema::hasTable($tableName)) {
                    continue;
                }

                $records = array_values(array_filter(
                    $records,
                    fn ($record) => ! empty($record['sync_id'] ?? null)
                ));

                if ($records === []) {
                    continue;
                }

                // Batch-load sync_id → local id maps once per table payload (avoids N+1).
                $fkLookups = [];
                foreach ($this->foreignKeyMapping as $foreignKey => $refTable) {
                    $syncKey = str_replace('_id', '_sync_id', $foreignKey);
                    $syncIds = collect($records)->pluck($syncKey)->filter()->unique()->values();
                    if ($syncIds->isEmpty() || ! Schema::hasTable($refTable)) {
                        $fkLookups[$syncKey] = [];

                        continue;
                    }
                    $fkLookups[$syncKey] = DB::table($refTable)
                        ->whereIn('sync_id', $syncIds->all())
                        ->pluck('id', 'sync_id')
                        ->all();
                }

                $existingBySyncId = [];
                if (Schema::hasColumn($tableName, 'sync_id')) {
                    $existingBySyncId = DB::table($tableName)
                        ->whereIn('sync_id', collect($records)->pluck('sync_id')->unique()->values()->all())
                        ->get()
                        ->keyBy('sync_id')
                        ->all();
                }

                DB::beginTransaction();
                try {
                    foreach ($records as $record) {
                        unset($record['id']);
                        if (Schema::hasColumn($tableName, 'is_synced')) {
                            $record['is_synced'] = true;
                        }

                        foreach ($this->foreignKeyMapping as $foreignKey => $refTable) {
                            $syncKey = str_replace('_id', '_sync_id', $foreignKey);
                            if (array_key_exists($syncKey, $record)) {
                                if ($record[$syncKey] !== null) {
                                    $record[$foreignKey] = $fkLookups[$syncKey][$record[$syncKey]] ?? null;
                                } else {
                                    $record[$foreignKey] = null;
                                }
                                unset($record[$syncKey]);
                            }
                        }

                        $exists = $existingBySyncId[$record['sync_id']] ?? null;
                        if ($exists) {
                            if (isset($record['updated_at'], $exists->updated_at)) {
                                if (strtotime($record['updated_at']) > strtotime($exists->updated_at)) {
                                    DB::table($tableName)->where('sync_id', $record['sync_id'])->update($record);
                                }
                            } else {
                                DB::table($tableName)->where('sync_id', $record['sync_id'])->update($record);
                            }
                        } else {
                            DB::table($tableName)->insert($record);
                        }
                        $rowCount++;
                    }
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            Log::info('sync.push', [
                'tenant' => tenant('id'),
                'rows' => $rowCount,
                'ms' => (int) ((microtime(true) - $started) * 1000),
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Live Sync Push Error: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function pull(Request $request)
    {
        $started = microtime(true);
        $lastSyncTime = $request->input('last_sync_time', '2000-01-01 00:00:00');
        $limit = max(50, min((int) $request->input('limit', config('sync.batch_size', 500)), 2000));
        $cursor = $request->input('cursor');
        $serverTime = now()->toDateTimeString();

        $startTableIndex = 0;
        $afterId = 0;
        if (is_string($cursor) && str_contains($cursor, ':')) {
            [$cursorTable, $cursorId] = explode(':', $cursor, 2);
            $startTableIndex = max(0, array_search($cursorTable, $this->tables, true) ?: 0);
            $afterId = (int) $cursorId;
            if (! in_array($cursorTable, $this->tables, true)) {
                $startTableIndex = 0;
                $afterId = 0;
            } else {
                $startTableIndex = array_search($cursorTable, $this->tables, true);
            }
        }

        $pullData = [];
        $remaining = $limit;
        $nextCursor = null;
        $stoppedAtTableIndex = null;
        $stoppedAfterId = null;

        for ($i = $startTableIndex; $i < count($this->tables); $i++) {
            $tableName = $this->tables[$i];

            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'updated_at')) {
                $afterId = 0;
                continue;
            }

            $query = DB::table($tableName)
                ->where('updated_at', '>', $lastSyncTime)
                ->orderBy('id');

            if ($i === $startTableIndex && $afterId > 0) {
                $query->where('id', '>', $afterId);
            }

            $records = $query->limit($remaining)->get();

            if ($records->isNotEmpty()) {
                // Batch-load FK → sync_id maps once per page (avoids N+1 on large pulls).
                $fkLookups = [];
                foreach ($this->foreignKeyMapping as $foreignKey => $refTable) {
                    $ids = $records->pluck($foreignKey)->filter()->unique()->values();
                    if ($ids->isEmpty()) {
                        $fkLookups[$foreignKey] = [];

                        continue;
                    }
                    $fkLookups[$foreignKey] = DB::table($refTable)
                        ->whereIn('id', $ids->all())
                        ->pluck('sync_id', 'id')
                        ->all();
                }

                $mappedRecords = $records->map(function ($item) use ($fkLookups) {
                    $record = (array) $item;
                    foreach ($this->foreignKeyMapping as $foreignKey => $refTable) {
                        if (array_key_exists($foreignKey, $record) && $record[$foreignKey] !== null) {
                            $syncKey = str_replace('_id', '_sync_id', $foreignKey);
                            $record[$syncKey] = $fkLookups[$foreignKey][$record[$foreignKey]] ?? null;
                        }
                    }

                    return $record;
                });

                $pullData[$tableName] = $mappedRecords->values()->all();
                $remaining -= $records->count();
                $nextCursor = $tableName.':'.$records->last()->id;
                $stoppedAtTableIndex = $i;
                $stoppedAfterId = (int) $records->last()->id;

                if ($remaining <= 0) {
                    break;
                }
            }

            $afterId = 0;
        }

        $hasMore = false;
        if ($stoppedAtTableIndex !== null) {
            $tableName = $this->tables[$stoppedAtTableIndex];
            if (DB::table($tableName)
                ->where('updated_at', '>', $lastSyncTime)
                ->where('id', '>', $stoppedAfterId)
                ->exists()) {
                $hasMore = true;
            } else {
                for ($j = $stoppedAtTableIndex + 1; $j < count($this->tables); $j++) {
                    $nextTable = $this->tables[$j];
                    if (! Schema::hasTable($nextTable) || ! Schema::hasColumn($nextTable, 'updated_at')) {
                        continue;
                    }
                    if (DB::table($nextTable)->where('updated_at', '>', $lastSyncTime)->exists()) {
                        $hasMore = true;
                        break;
                    }
                }
            }
        }

        $rowCount = collect($pullData)->sum(fn ($rows) => is_countable($rows) ? count($rows) : 0);
        Log::info('sync.pull', [
            'tenant' => tenant('id'),
            'rows' => $rowCount,
            'has_more' => $hasMore,
            'ms' => (int) ((microtime(true) - $started) * 1000),
        ]);

        return response()->json([
            'status' => 'success',
            'sync_data' => $pullData,
            'server_time' => $serverTime,
            'has_more' => $hasMore,
            'next_cursor' => $hasMore ? $nextCursor : null,
        ]);
    }

    public function pushMedia(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file',
                'path' => 'required|string',
            ]);

            $path = $request->input('path');
            $file = $request->file('file');
            $uploadDisk = config('filesystems.upload_disk', 'public');

            if (\Illuminate\Support\Facades\Storage::disk($uploadDisk)->exists($path)) {
                \Illuminate\Support\Facades\Storage::disk($uploadDisk)->delete($path);
            }

            \Illuminate\Support\Facades\Storage::disk($uploadDisk)->put($path, file_get_contents($file->getRealPath()));

            return response()->json(['status' => 'success', 'path' => $path]);
        } catch (\Exception $e) {
            Log::error('Live Sync Media Push Error: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
