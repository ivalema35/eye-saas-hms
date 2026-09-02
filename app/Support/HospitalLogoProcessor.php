<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Normalizes hospital logo uploads to a consistent display size.
 */
class HospitalLogoProcessor
{
    public const MAX_WIDTH = 400;

    public const MAX_HEIGHT = 120;

    public static function storeUploadedLogo(UploadedFile $file, int $tenantId): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $dir = "tenants/{$tenantId}/logo";
        $basename = 'logo_'.time();

        if ($ext === 'svg' || ! extension_loaded('gd')) {
            return $file->storeAs($dir, "{$basename}.{$ext}", 'public');
        }

        return self::resizeRasterToDisk($file->getRealPath(), $ext, $dir, $basename);
    }

    public static function storeProcessedBase64(string $dataUrl, int $tenantId): string
    {
        if (! str_starts_with($dataUrl, 'data:image')) {
            throw new \InvalidArgumentException('Invalid logo image data.');
        }

        $dir = "tenants/{$tenantId}/logo";
        $basename = 'logo_nobg_'.time();
        $binary = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $dataUrl), true);

        if ($binary === false) {
            throw new \InvalidArgumentException('Invalid logo base64 payload.');
        }

        if (! extension_loaded('gd')) {
            $relativePath = "{$dir}/{$basename}.png";
            Storage::disk('public')->put($relativePath, $binary);

            return $relativePath;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'hlogo_');
        file_put_contents($tmp, $binary);

        try {
            return self::resizeRasterToDisk($tmp, 'png', $dir, $basename);
        } finally {
            @unlink($tmp);
        }
    }

    private static function resizeRasterToDisk(string $sourcePath, string $ext, string $dir, string $basename): string
    {
        $image = self::loadImage($sourcePath, $ext);

        if (! $image) {
            $fallbackExt = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) ? $ext : 'png';
            $relative = "{$dir}/{$basename}.{$fallbackExt}";
            Storage::disk('public')->put($relative, (string) file_get_contents($sourcePath));

            return $relative;
        }

        $srcW = imagesx($image);
        $srcH = imagesy($image);
        [$dstW, $dstH] = self::fitDimensions($srcW, $srcH, self::MAX_WIDTH, self::MAX_HEIGHT);

        $canvas = imagecreatetruecolor($dstW, $dstH);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagealphablending($canvas, true);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
        imagedestroy($image);

        Storage::disk('public')->makeDirectory($dir);
        $relative = "{$dir}/{$basename}.png";
        imagepng($canvas, Storage::disk('public')->path($relative), 6);
        imagedestroy($canvas);

        return $relative;
    }

    /** @return array{0: int, 1: int} */
    private static function fitDimensions(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return [$width, $height];
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height);

        return [
            max(1, (int) round($width * $ratio)),
            max(1, (int) round($height * $ratio)),
        ];
    }

    private static function loadImage(string $path, string $ext): \GdImage|false
    {
        return match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }
}
