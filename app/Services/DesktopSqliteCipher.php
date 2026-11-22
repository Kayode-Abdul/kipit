<?php

namespace App\Services;

use App\Helpers\DesktopHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Optional SQLCipher support when the bundled PHP SQLite is compiled with SQLCipher.
 * Until then, Electron seals the DB file at rest (database.sealed) and only unlocks
 * a runtime copy while the app is open.
 */
class DesktopSqliteCipher
{
    public function applyKeyIfAvailable(): void
    {
        if (! DesktopHelper::isDesktopApp()) {
            return;
        }

        try {
            $version = DB::select('PRAGMA cipher_version');
            $cipherVersion = $version[0]->cipher_version ?? null;
            if (! $cipherVersion) {
                return;
            }

            $key = config('desktop.sqlcipher_key') ?: config('app.key');
            if (! $key) {
                return;
            }

            // SQLCipher unlock (no-op on stock SQLite — pragma is ignored / errors caught).
            DB::statement('PRAGMA key = '.$this->quoteKey($key));
            Log::info('Desktop SQLCipher enabled', ['cipher_version' => $cipherVersion]);
        } catch (\Throwable $e) {
            // Stock SQLite has no cipher_version — expected until a SQLCipher PHP build ships.
        }
    }

    protected function quoteKey(string $key): string
    {
        $escaped = str_replace("'", "''", $key);

        return "'{$escaped}'";
    }
}
