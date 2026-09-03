<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Public disk files: mirror into public/storage (for hosts without symlink)
 * and build URLs via /files/… route when needed.
 */
final class PublicStorage
{
    public static function normalizePath(string $relativePath): ?string
    {
        $path = str_replace('\\', '/', ltrim($relativePath, '/'));

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        return $path;
    }

    public static function exists(?string $relativePath): bool
    {
        $path = $relativePath ? self::normalizePath($relativePath) : null;

        return $path !== null && Storage::disk('public')->exists($path);
    }

    /**
     * Best-effort copy to public/storage (optional — /files/ route does not need this).
     * Never throws; upload must succeed even when public/storage is not writable.
     */
    public static function mirror(?string $relativePath): bool
    {
        try {
            $path = $relativePath ? self::normalizePath($relativePath) : null;
            if ($path === null || ! Storage::disk('public')->exists($path)) {
                return false;
            }

            $source = storage_path('app/public/'.$path);
            if (! is_file($source) || ! is_readable($source)) {
                return false;
            }

            $destDir = public_path('storage');
            if (! is_dir($destDir) && ! @mkdir($destDir, 0755, true) && ! is_dir($destDir)) {
                return false;
            }

            if (! is_writable($destDir)) {
                return false;
            }

            $dest = public_path('storage/'.$path);
            $destParent = dirname($dest);
            if (! is_dir($destParent) && ! @mkdir($destParent, 0755, true) && ! is_dir($destParent)) {
                return false;
            }

            if (! is_writable($destParent)) {
                return false;
            }

            return @copy($source, $dest);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function mirrorDirectory(string $directory = ''): int
    {
        $directory = self::normalizePath($directory) ?? '';
        $count = 0;

        $files = Storage::disk('public')->allFiles($directory);
        foreach ($files as $file) {
            if (self::mirror($file)) {
                $count++;
            }
        }

        return $count;
    }

    public static function url(?string $relativePath): ?string
    {
        if (! self::exists($relativePath)) {
            return null;
        }

        $path = self::normalizePath($relativePath);

        return route('public.files', ['path' => $path]);
    }
}
