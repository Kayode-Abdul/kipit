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
        $payload = $request->input('sync_data', []);

        DB::beginTransaction();
        try {
            foreach ($payload as $tableName => $records) {
                if (empty($records) || ! Schema::hasTable($tableName)) {
                    continue;
                }

                foreach ($records as $record) {
                    if (empty($record['sync_id'])) {
                        continue;
                    }

                    unset($record['id']);
                    $record['is_synced'] = true;

                    foreach ($this->foreignKeyMapping as $foreignKey => $refTable) {
                        $syncKey = str_replace('_id', '_sync_id', $foreignKey);
                        if (array_key_exists($syncKey, $record)) {
                            if ($record[$syncKey] !== null) {
                                $refRecord = DB::table($refTable)->where('sync_id', $record[$syncKey])->first();
                                $record[$foreignKey] = $refRecord ? $refRecord->id : null;
                            } else {
                                $record[$foreignKey] = null;
                            }
                            unset($record[$syncKey]);
                        }
                    }

                    $exists = DB::table($tableName)->where('sync_id', $record['sync_id'])->first();
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
                }
            }
            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Live Sync Push Error: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function pull(Request $request)
    {
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
                $mappedRecords = $records->map(function ($item) {
                    $record = (array) $item;
                    foreach ($this->foreignKeyMapping as $foreignKey => $refTable) {
                        if (array_key_exists($foreignKey, $record) && $record[$foreignKey] !== null) {
                            $refRecord = DB::table($refTable)->where('id', $record[$foreignKey])->first();
                            $syncKey = str_replace('_id', '_sync_id', $foreignKey);
                            $record[$syncKey] = $refRecord ? $refRecord->sync_id : null;
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
