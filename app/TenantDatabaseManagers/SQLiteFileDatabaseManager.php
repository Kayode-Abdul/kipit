<?php

declare(strict_types=1);

namespace App\TenantDatabaseManagers;

use Stancl\Tenancy\Contracts\TenantDatabaseManager;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

/**
 * Creates one SQLite file per shop under database/ (e.g. database/kipit_mango).
 * Safe for shared hosting where MySQL users cannot CREATE/DROP DATABASE.
 */
class SQLiteFileDatabaseManager implements TenantDatabaseManager
{
    public function createDatabase(TenantWithDatabase $tenant): bool
    {
        $path = $this->path($tenant->database()->getName());

        try {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            if (! file_exists($path)) {
                file_put_contents($path, '');
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function deleteDatabase(TenantWithDatabase $tenant): bool
    {
        $path = $this->path($tenant->database()->getName());

        try {
            if (file_exists($path)) {
                return unlink($path);
            }

            // Already gone (or never created as a file) — treat as success
            // so admin delete works after failed MySQL attempts.
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function databaseExists(string $name): bool
    {
        return file_exists($this->path($name));
    }

    public function makeConnectionConfig(array $baseConfig, string $databaseName): array
    {
        $baseConfig['driver'] = 'sqlite';
        $baseConfig['database'] = $this->path($databaseName);
        unset($baseConfig['url'], $baseConfig['host'], $baseConfig['port'], $baseConfig['username'], $baseConfig['password'], $baseConfig['unix_socket']);

        return $baseConfig;
    }

    public function setConnection(string $connection): void
    {
        // SQLite files do not need a server connection.
    }

    protected function path(?string $name): string
    {
        return database_path((string) $name);
    }
}
