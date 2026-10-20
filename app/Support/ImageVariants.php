<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Versi kecil (WebP) gambar di disk public agar ponsel tidak mengunduh gambar 1024px+.
 * Contoh: wp/2026/09/foto.jpg → wp/2026/09/foto-480w.webp & foto-960w.webp
 */
class ImageVariants
{
    public const WIDTHS = [480, 960];

    private const RASTER = ['jpg', 'jpeg', 'png', 'webp'];

    public static function variantPath(string $path, int $width): string
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);

        return ($dir === '.' ? '' : $dir.'/').pathinfo($path, PATHINFO_FILENAME)."-{$width}w.webp";
    }

    public static function isVariant(string $path): bool
    {
        return (bool) preg_match('/-\d+w\.webp$/', $path);
    }

    /** Buat varian yang belum ada; mengembalikan jumlah file baru. */
    public static function generate(string $path): int
    {
        $disk = Storage::disk('public');
        if (static::isVariant($path) || ! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::RASTER, true) || ! $disk->exists($path)) {
            return 0;
        }

        try {
            $source = @imagecreatefromstring($disk->get($path));
        } catch (Throwable) {
            $source = false;
        }
        if (! $source) {
            return 0;
        }

        $created = 0;
        $width = imagesx($source);
        foreach (self::WIDTHS as $target) {
            $variant = static::variantPath($path, $target);
            if ($width <= $target || $disk->exists($variant)) {
                continue;
            }
            $scaled = imagescale($source, $target, -1, IMG_BICUBIC);
            imagepalettetotruecolor($scaled);
            imagealphablending($scaled, false);
            imagesavealpha($scaled, true);
            ob_start();
            imagewebp($scaled, null, 78);
            $disk->put($variant, ob_get_clean());
            imagedestroy($scaled);
            $created++;
        }
        imagedestroy($source);
        Cache::forget('img-meta:'.$path);

        return $created;
    }

    /**
     * Data siap pakai untuk <img>: src (varian terkecil yang cocok), srcset, width/height asli.
     *
     * @return array{src: string, srcset: string|null, width: int|null, height: int|null}|null
     */
    public static function meta(string $path): ?array
    {
        $path = ltrim(Str::after($path, '/storage/'), '/');

        return Cache::rememberForever('img-meta:'.$path, function () use ($path) {
            $disk = Storage::disk('public');
            if (! $disk->exists($path)) {
                return null;
            }

            $size = @getimagesize($disk->path($path)) ?: [null, null];
            $set = [];
            foreach (self::WIDTHS as $width) {
                if ($disk->exists($variant = static::variantPath($path, $width))) {
                    $set[] = '/storage/'.$variant." {$width}w";
                }
            }
            if ($set && $size[0]) {
                $set[] = '/storage/'.$path." {$size[0]}w";
            }

            return [
                'src' => '/storage/'.$path,
                'srcset' => $set ? implode(', ', $set) : null,
                'width' => $size[0],
                'height' => $size[1],
            ];
        });
    }
}
