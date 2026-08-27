<?php

namespace App\Services;

use App\Helpers\DesktopHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DatabaseIntegrityService
{
    /**
     * Tables whose content is critical and should be integrity-checked.
     */
    protected array $protectedTables = [
        'users',
        'roles',
        'permissions',
        'role_has_permissions',
        'model_has_roles',
        'model_has_permissions',
        'abouts',
    ];

    /**
     * Compute an HMAC checksum of critical database tables and store it.
     * Called after migrations/seeding on startup.
     */
    public function generateChecksum(): string
    {
        $dataFingerprint = $this->buildDataFingerprint();
        $hmacKey = $this->getHmacKey();
        $checksum = hash_hmac('sha256', $dataFingerprint, $hmacKey);

        $this->storeChecksum($checksum);

        return $checksum;
    }

    /**
     * Verify the current database state against the stored checksum.
     *
     * @return array{valid: bool, message: string}
     */
    public function verify(): array
    {
        $checksumPath = $this->getChecksumPath();

        if (!file_exists($checksumPath)) {
            // No previous checksum — first run, generate and store
            $this->generateChecksum();

            return [
                'valid' => true,
                'message' => 'First run — checksum generated and stored.',
            ];
        }

        $storedChecksum = trim(file_get_contents($checksumPath));
        $currentFingerprint = $this->buildDataFingerprint();
        $hmacKey = $this->getHmacKey();
        $currentChecksum = hash_hmac('sha256', $currentFingerprint, $hmacKey);

        if (hash_equals($storedChecksum, $currentChecksum)) {
            return [
                'valid' => true,
                'message' => 'Database integrity verified — no tampering detected.',
            ];
        }

        Log::warning('Database integrity check FAILED. Stored checksum does not match computed checksum.', [
            'stored' => $storedChecksum,
            'computed' => $currentChecksum,
        ]);

        return [
            'valid' => false,
            'message' => 'Database integrity check failed. The database may have been tampered with outside the application.',
        ];
    }

    /**
     * Update the stored checksum to reflect current legitimate state.
     * Called after authorized data changes (sync, admin operations).
     */
    public function refreshChecksum(): string
    {
        return $this->generateChecksum();
    }

    /**
     * Build a deterministic fingerprint string from protected table data.
     */
    protected function buildDataFingerprint(): string
    {
        $parts = [];

        foreach ($this->protectedTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                // Get a deterministic representation: sorted rows as JSON
                $rows = DB::table($table)
                    ->orderBy('id')
                    ->get()
                    ->map(function ($row) {
                        // Convert to array and sort by key for determinism
                        $data = (array) $row;
                        ksort($data);

                        return $data;
                    })
                    ->toArray();

                $parts[] = $table . ':' . json_encode($rows, JSON_UNESCAPED_UNICODE);
            } catch (\Throwable $e) {
                // If a table can't be read (e.g., missing 'id' column), use row count
                try {
                    $count = DB::table($table)->count();
                    $parts[] = $table . ':count:' . $count;
                } catch (\Throwable $e2) {
                    $parts[] = $table . ':error';
                }
            }
        }

        return implode('|', $parts);
    }

    /**
     * Derive the HMAC key from the app key + a desktop-specific salt.
     */
    protected function getHmacKey(): string
    {
        $appKey = config('app.key', 'kipit-desktop-fallback-key');
        $salt = 'kipit-desktop-integrity-v1';

        return hash('sha256', $appKey . $salt);
    }

    /**
     * Get the path to the checksum file.
     */
    protected function getChecksumPath(): string
    {
        return DesktopHelper::getConfigDir() . DIRECTORY_SEPARATOR . 'database.checksum';
    }

    /**
     * Write the checksum to the file system.
     */
    protected function storeChecksum(string $checksum): void
    {
        $configDir = DesktopHelper::getConfigDir();
        if (!is_dir($configDir)) {
            mkdir($configDir, 0700, true);
        }

        $checksumPath = $this->getChecksumPath();
        file_put_contents($checksumPath, $checksum);

        // Set restrictive permissions on the checksum file (macOS/Linux)
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($checksumPath, 0600);
        }
    }
}
