<?php

namespace App\Services;

use App\Helpers\DesktopHelper;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileEncryptionService
{
    /**
     * Get the native storage path (similar to RunSyncEngine logic).
     */
    public static function getNativeStoragePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            $path = ltrim($path, '/');
        }

        $appDataPath = env('APPDATA') ?: (env('HOME') . '/Library/Application Support');
        $appName = env('APP_NAME', 'Kipit'); // fallback
        
        return $appDataPath . '/' . $appName . '/storage/app/public/' . $path;
    }

    /**
     * Encrypt a file and save it in-place using absolute path.
     */
    public static function encryptAndStore(string $absolutePath): void
    {
        if (!DesktopHelper::isDesktopApp()) {
            return; // Only encrypt on desktop
        }

        if (!file_exists($absolutePath)) {
            return;
        }

        $contents = file_get_contents($absolutePath);
        
        // Prevent double encryption
        if (Str::startsWith($contents, 'eyJ')) {
            try {
                Crypt::decryptString($contents);
                return; // already encrypted
            } catch (\Exception $e) {
                // Not a valid crypt payload, proceed with encryption
            }
        }

        $encrypted = Crypt::encryptString($contents);
        file_put_contents($absolutePath, $encrypted);
    }

    /**
     * Helper to encrypt a file right after it's been stored via Laravel's Storage.
     */
    public static function encryptStoredFile(string $path, string $disk = 'public'): void
    {
        if (!DesktopHelper::isDesktopApp()) {
            return;
        }

        $absolutePath = Storage::disk($disk)->path($path);
        
        if (!file_exists($absolutePath)) {
            $nativePath = self::getNativeStoragePath($path);
            if (file_exists($nativePath)) {
                $absolutePath = $nativePath;
            }
        }

        self::encryptAndStore($absolutePath);
    }

    /**
     * Read a file and decrypt it. Returns raw bytes.
     * If decryption fails (file is not encrypted), returns the raw file contents.
     */
    public static function getDecryptedContents(string $absolutePath): string
    {
        if (!file_exists($absolutePath)) {
            return '';
        }

        $contents = file_get_contents($absolutePath);

        if (!DesktopHelper::isDesktopApp()) {
            return $contents; // Web app serves files unencrypted
        }

        if (Str::startsWith($contents, 'eyJ')) {
            try {
                return Crypt::decryptString($contents);
            } catch (\Exception $e) {
                return $contents;
            }
        }

        return $contents;
    }

    /**
     * Decrypt to a temporary file and return its path (useful for response()->file()).
     */
    public static function decryptToTempFile(string $absolutePath): string
    {
        $contents = self::getDecryptedContents($absolutePath);
        
        $tempPath = sys_get_temp_dir() . '/' . basename($absolutePath) . '_' . uniqid();
        file_put_contents($tempPath, $contents);
        
        return $tempPath;
    }
}
