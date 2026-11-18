<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Generates resized copies of uploaded product photos (WebP when the
 * server's GD supports it, JPEG otherwise) and strips camera metadata
 * (EXIF, including GPS position) from the original.
 */
class ImageVariants
{
    public const MAX_ORIGINAL = 2400;

    public function format(): string
    {
        return function_exists('gd_info') && (gd_info()['WebP Support'] ?? false) ? 'webp' : 'jpg';
    }

    public static function variantPath(string $path, int $size, string $format): string
    {
        $info = pathinfo($path);
        $dir = $info['dirname'] === '.' ? '' : $info['dirname'].'/';

        return "{$dir}{$info['filename']}-{$size}.{$format}";
    }

    /**
     * @return array{width:int,height:int,format:string}|null
     */
    public function generate(string $path): ?array
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        try {
            $manager = new ImageManager(new Driver);
            $absolute = $disk->path($path);

            // Re-encode the original: fixes orientation, caps the size,
            // and drops EXIF data (phones store the GPS position there).
            $original = $manager->read($absolute);
            $original->scaleDown(width: self::MAX_ORIGINAL, height: self::MAX_ORIGINAL);
            $original->save($absolute, quality: 90);

            $format = $this->format();

            foreach (config('shop.image_sizes') as $size) {
                $image = $manager->read($absolute);
                $image->scaleDown(width: $size, height: $size);
                $image->save($disk->path(self::variantPath($path, $size, $format)), quality: 82);
            }

            return ['width' => $original->width(), 'height' => $original->height(), 'format' => $format];
        } catch (Throwable $e) {
            Log::warning('Image variant generation failed', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function delete(string $path, ?string $format): void
    {
        $disk = Storage::disk('public');
        $files = [$path];

        foreach (config('shop.image_sizes') as $size) {
            foreach (array_filter([$format, 'webp', 'jpg']) as $fmt) {
                $files[] = self::variantPath($path, $size, $fmt);
            }
        }

        $disk->delete(array_unique($files));
    }
}
