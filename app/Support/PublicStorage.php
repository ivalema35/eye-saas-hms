<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
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
     * Copy storage/app/public/{path} → public/storage/{path}
     */
    public static function mirror(?string $relativePath): void
    {
        $path = $relativePath ? self::normalizePath($relativePath) : null;
        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return;
        }

        $source = storage_path('app/public/'.$path);
        $dest = public_path('storage/'.$path);

        File::ensureDirectoryExists(dirname($dest));

        if (! is_file($source)) {
            return;
        }

        copy($source, $dest);
    }

    public static function mirrorDirectory(string $directory = ''): int
    {
        $directory = self::normalizePath($directory) ?? '';
        $count = 0;

        $files = Storage::disk('public')->allFiles($directory);
        foreach ($files as $file) {
            self::mirror($file);
            $count++;
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
