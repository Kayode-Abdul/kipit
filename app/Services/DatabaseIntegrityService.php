<?php

namespace App\Services;

use App\Helpers\DesktopHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DatabaseIntegrityService
{
    public const CACHE_INTEGRITY_FAILED = 'desktop_integrity_failed';

    public const CACHE_CLOUD_WINS_REPAIR = 'desktop_cloud_wins_repair';

    /**
     * Auth + commercial data covered by integrity fingerprints.
     *
     * @var list<string>
     */
    protected array $protectedTables = [
        'users',
        'roles',
        'permissions',
        'role_has_permissions',
        'model_has_roles',
        'model_has_permissions',
        'products',
        'stocks',
        'categories',
        'members',
        'sellings',
        'selling_details',
        'purchasings',
        'stock_opnames',
        'stock_opname_items',
        'receivables',
        'receivable_items',
        'receivable_payments',
        'cash_drawers',
        'payment_methods',
        'suppliers',
        'vouchers',
        'settings',
        'abouts',
    ];

    /**
     * @return array{valid: bool, message: string, checksum?: string}
     */
    public function verify(): array
    {
        if (! DesktopHelper::isDesktopApp()) {
            return ['valid' => true, 'message' => 'Not desktop — skipped.'];
        }

        $checksumPath = $this->getChecksumPath();

        if (! file_exists($checksumPath)) {
            $checksum = $this->generateChecksum();

            return [
                'valid' => true,
                'message' => 'First run — checksum generated and stored.',
                'checksum' => $checksum,
            ];
        }

        $storedChecksum = trim((string) file_get_contents($checksumPath));
        $currentChecksum = $this->computeChecksum();

        if (hash_equals($storedChecksum, $currentChecksum)) {
            Cache::forget(self::CACHE_INTEGRITY_FAILED);

            return [
                'valid' => true,
                'message' => 'Database integrity verified — no tampering detected.',
                'checksum' => $currentChecksum,
            ];
        }

        Cache::put(self::CACHE_INTEGRITY_FAILED, true, 86400);

        Log::warning('Database integrity check FAILED.', [
            'stored' => $storedChecksum,
            'computed' => $currentChecksum,
        ]);

        return [
            'valid' => false,
            'message' => 'Database integrity check failed. Local data may have been altered outside Kipit POS. Cloud repair is required.',
            'checksum' => $currentChecksum,
        ];
    }

    public function generateChecksum(): string
    {
        $checksum = $this->computeChecksum();
        $this->storeChecksum($checksum);
        Cache::forget(self::CACHE_INTEGRITY_FAILED);

        return $checksum;
    }

    public function refreshChecksum(): string
    {
        return $this->generateChecksum();
    }

    public function computeChecksum(): string
    {
        return hash_hmac('sha256', $this->buildDataFingerprint(), $this->getHmacKey());
    }

    public function markCloudWinsRepair(): void
    {
        Cache::put(self::CACHE_CLOUD_WINS_REPAIR, true, 86400);
        Cache::put(self::CACHE_INTEGRITY_FAILED, true, 86400);
    }

    public function clearCloudWinsRepair(): void
    {
        Cache::forget(self::CACHE_CLOUD_WINS_REPAIR);
        Cache::forget(self::CACHE_INTEGRITY_FAILED);
    }

    public function shouldPreferCloud(): bool
    {
        return (bool) Cache::get(self::CACHE_CLOUD_WINS_REPAIR, false)
            || (bool) Cache::get(self::CACHE_INTEGRITY_FAILED, false);
    }

    /**
     * @return list<string>
     */
    public function protectedTables(): array
    {
        return $this->protectedTables;
    }

    protected function buildDataFingerprint(): string
    {
        $parts = [];

        foreach ($this->protectedTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $parts[] = $this->tableFingerprint($table);
        }

        return implode('|', $parts);
    }

    protected function tableFingerprint(string $table): string
    {
        try {
            $count = (int) DB::table($table)->count();
            $maxUpdated = '';
            if (Schema::hasColumn($table, 'updated_at')) {
                $maxUpdated = (string) (DB::table($table)->max('updated_at') ?? '');
            }

            $hash = hash_init('sha256');
            $columns = ['id'];
            if (Schema::hasColumn($table, 'sync_id')) {
                $columns[] = 'sync_id';
            }
            if (Schema::hasColumn($table, 'updated_at')) {
                $columns[] = 'updated_at';
            }

            if (Schema::hasColumn($table, 'id')) {
                DB::table($table)
                    ->orderBy('id')
                    ->select($columns)
                    ->chunkById(2_000, function ($rows) use ($hash) {
                        foreach ($rows as $row) {
                            $data = (array) $row;
                            ksort($data);
                            hash_update($hash, json_encode($data, JSON_UNESCAPED_UNICODE).';');
                        }
                    });
            } else {
                hash_update($hash, 'count:'.$count);
            }

            return $table.':'.$count.':'.$maxUpdated.':'.hash_final($hash);
        } catch (\Throwable $e) {
            Log::warning("Integrity fingerprint failed for {$table}: ".$e->getMessage());

            return $table.':error';
        }
    }

    protected function getHmacKey(): string
    {
        $appKey = config('app.key') ?: 'base64:kipitDesktopSecretKey2026DefaultSaltKey=';

        return hash('sha256', $appKey.'kipit-desktop-integrity-v2');
    }

    protected function getChecksumPath(): string
    {
        return DesktopHelper::getConfigDir().DIRECTORY_SEPARATOR.'database.checksum';
    }

    protected function storeChecksum(string $checksum): void
    {
        $configDir = DesktopHelper::getConfigDir();
        if (! is_dir($configDir)) {
            mkdir($configDir, 0700, true);
        }

        $checksumPath = $this->getChecksumPath();
        file_put_contents($checksumPath, $checksum);

        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($checksumPath, 0600);
        }
    }
}
