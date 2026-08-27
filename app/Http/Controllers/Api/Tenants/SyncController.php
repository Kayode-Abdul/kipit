<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    public function push(Request $request)
    {
        $payload = $request->input('sync_data', []);
        
        DB::beginTransaction();
        try {
            foreach ($payload as $tableName => $records) {
                if (!empty($records)) {
                    foreach ($records as $record) {
                        if (empty($record['sync_id'])) {
                            continue;
                        }
                        
                        // Sanitize record to prevent local ID collision
                        unset($record['id']);
                        $record['is_synced'] = true;
                        
                        // In a fully developed sync engine, foreign key translation based on sync_id 
                        // would occur here before insertion. (e.g. selling_id -> real selling id).
                        
                        $exists = DB::table($tableName)->where('sync_id', $record['sync_id'])->first();
                        if ($exists) {
                            // Basic conflict resolution based on updated_at
                            if (isset($record['updated_at']) && isset($exists->updated_at)) {
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
            }
            DB::commit();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Live Sync Push Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function pull(Request $request)
    {
        $lastSyncTime = $request->input('last_sync_time', '2000-01-01 00:00:00');
        $serverTime = now()->toDateTimeString();
        
        $tables = [
            'users', 'categories', 'products', 'members', 'sellings', 'selling_details',
            'stocks', 'suppliers', 'purchasings', 'stock_opnames', 'stock_opname_items',
            'receivables', 'receivable_items', 'receivable_payments', 'cash_drawers',
            'cart_items'
        ];
        
        $pullData = [];
        foreach ($tables as $tableName) {
            $records = DB::table($tableName)
                ->where('updated_at', '>', $lastSyncTime)
                ->get();
                
            if ($records->isNotEmpty()) {
                $pullData[$tableName] = $records;
            }
        }
        
        return response()->json([
            'status' => 'success',
            'sync_data' => $pullData,
            'server_time' => $serverTime
        ]);
    }
}
