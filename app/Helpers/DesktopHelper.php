<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class DesktopHelper
{
    /**
     * Check if the app is running as a NativePHP desktop application.
     */
    public static function isDesktopApp(): bool
    {
        if (getenv('NATIVEPHP_RUNNING') || env('NATIVEPHP_RUNNING') || getenv('NATIVEPHP_STORAGE_PATH') || getenv('NATIVEPHP_DATABASE_PATH')) {
            return true;
        }

        if (defined('NATIVEPHP_RUNNING') || class_exists(\Native\Laravel\Facades\Window::class)) {
            if (getenv('NATIVEPHP_PORT') || getenv('NATIVE_PHP') || str_contains(base_path(), 'NativePHP') || str_contains(base_path(), 'Kipit.app')) {
                return true;
            }
        }

        if (isset($_SERVER['HTTP_USER_AGENT'])
            && (strpos($_SERVER['HTTP_USER_AGENT'], 'Electron') !== false
                || strpos($_SERVER['HTTP_USER_AGENT'], 'kipit') !== false)) {
            return true;
        }

        return false;
    }

    /**
     * Check if this is the first time the desktop app is being launched.
     * A first launch means the local database has no users.
     */
    public static function isFirstLaunch(): bool
    {
        if (!static::isDesktopApp()) {
            return false;
        }

        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('users')) {
                return true;
            }

            return \Illuminate\Support\Facades\DB::table('users')->count() === 0;
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * Check if the desktop setup wizard has been completed.
     */
    public static function isSetupComplete(): bool
    {
        if (!static::isDesktopApp()) {
            return true;
        }

        $flagPath = static::getConfigDir() . DIRECTORY_SEPARATOR . 'setup_complete';

        return file_exists($flagPath);
    }

    /**
     * Mark the desktop setup as complete.
     */
    public static function markSetupComplete(): void
    {
        $configDir = static::getConfigDir();
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        file_put_contents($configDir . DIRECTORY_SEPARATOR . 'setup_complete', now()->toISOString());
    }

    /**
     * Check if the first admin login has been completed.
     * On a fresh install, the first login must be from an admin/owner.
     */
    public static function isFirstLoginComplete(): bool
    {
        if (!static::isDesktopApp()) {
            return true;
        }

        $flagPath = static::getConfigDir() . DIRECTORY_SEPARATOR . 'first_login_complete';

        return file_exists($flagPath);
    }

    /**
     * Mark the first admin login as complete.
     * After this, any user with valid credentials can log in.
     */
    public static function markFirstLoginComplete(): void
    {
        $configDir = static::getConfigDir();
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        file_put_contents(
            $configDir . DIRECTORY_SEPARATOR . 'first_login_complete',
            now()->toISOString()
        );
    }

    /**
     * Get the OS-specific SQLite database path for the desktop app.
     */
    public static function getSqlitePath(): string
    {
        if (getenv('NATIVEPHP_DATABASE_PATH')) {
            return getenv('NATIVEPHP_DATABASE_PATH');
        }

        if (env('NATIVEPHP_DATABASE_PATH')) {
            return env('NATIVEPHP_DATABASE_PATH');
        }

        if (PHP_OS_FAMILY === 'Darwin') {
            return getenv('HOME') . '/Library/Application Support/kipit/database.sqlite';
        } elseif (PHP_OS_FAMILY === 'Windows') {
            return getenv('APPDATA') . '\\kipit\\database.sqlite';
        }

        return getenv('HOME') . '/.config/kipit/database.sqlite';
    }

    /**
     * Get the OS-specific config directory for the desktop app.
     */
    public static function getConfigDir(): string
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            return getenv('HOME') . '/Library/Application Support/kipit';
        } elseif (PHP_OS_FAMILY === 'Windows') {
            return getenv('APPDATA') . '\\kipit';
        }

        return getenv('HOME') . '/.config/kipit';
    }

    /**
     * Get the stored server URL for sync operations.
     */
    public static function getServerUrl(): ?string
    {
        $config = static::readServerConfig();

        return $config['server_url'] ?? null;
    }

    /**
     * Get the stored sync token for server communication.
     */
    public static function getSyncToken(): ?string
    {
        $config = static::readServerConfig();

        return $config['sync_token'] ?? null;
    }

    /**
     * Store server configuration for future sync operations.
     * The config is encrypted at rest to protect the sync token.
     */
    public static function storeServerConfig(string $serverUrl, string $syncToken): void
    {
        $configDir = static::getConfigDir();
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        $config = [
            'server_url' => $serverUrl,
            'sync_token' => $syncToken,
            'configured_at' => now()->toISOString(),
        ];

        $configPath = $configDir . DIRECTORY_SEPARATOR . 'server_config.json';

        try {
            $encrypted = Crypt::encryptString(json_encode($config, JSON_PRETTY_PRINT));
            file_put_contents($configPath, $encrypted);
        } catch (\Throwable $e) {
            // Fallback: write plaintext if encryption fails (e.g., missing APP_KEY)
            Log::warning('Failed to encrypt server config, writing plaintext: ' . $e->getMessage());
            file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT));
        }

        // Set restrictive file permissions (macOS/Linux)
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($configPath, 0600);
        }
    }

    /**
     * Read and decrypt the server configuration file.
     * Handles both encrypted and legacy plaintext formats for upgrade compatibility.
     */
    protected static function readServerConfig(): array
    {
        $configPath = static::getConfigDir() . DIRECTORY_SEPARATOR . 'server_config.json';

        if (!file_exists($configPath)) {
            return [];
        }

        $raw = file_get_contents($configPath);

        // Try to decrypt (encrypted format)
        try {
            $decrypted = Crypt::decryptString($raw);
            $config = json_decode($decrypted, true);

            if (is_array($config)) {
                return $config;
            }
        } catch (\Throwable $e) {
            // Decryption failed — might be legacy plaintext format
        }

        // Fallback: try reading as plain JSON (legacy/upgrade path)
        $config = json_decode($raw, true);

        if (is_array($config) && isset($config['server_url'])) {
            // Re-encrypt the legacy plaintext config for future reads
            try {
                if (isset($config['sync_token'])) {
                    static::storeServerConfig($config['server_url'], $config['sync_token']);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to re-encrypt legacy server config: ' . $e->getMessage());
            }

            return $config;
        }

        return [];
    }
}
