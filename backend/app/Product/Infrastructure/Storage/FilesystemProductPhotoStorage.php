<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Storage;

use App\Product\Domain\Exception\InvalidProductPhotoException;
use App\Product\Domain\Interfaces\ProductPhotoStorageInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

/**
 * Stores product photos on a configured Laravel filesystem disk (local/public in dev,
 * s3 → Cloudflare R2 in production). Returns the public URL to persist in image_src.
 *
 * Images are resized to a maximum of 1080 px on the longest side and converted to
 * WebP format (quality 85) before storage.
 */
class FilesystemProductPhotoStorage implements ProductPhotoStorageInterface
{
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const MAX_DIMENSION = 4096;
    private const OUTPUT_EXTENSION = 'webp';
    private const MAX_IMAGE_DIMENSION = 1080;
    private const WEBP_QUALITY = 85;

    public function store(
        string $temporaryPath,
        string $restaurantUuid,
        string $productUuid,
        ?string $previousImageSrc,
    ): string {
        $contents = @file_get_contents($temporaryPath);
        if ($contents === false || $contents === '') {
            throw InvalidProductPhotoException::unreadable();
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->buffer($contents);
        $extension = self::MIME_EXTENSIONS[$mime] ?? null;
        if ($extension === null) {
            throw InvalidProductPhotoException::unreadable();
        }

        [$width, $height] = getimagesizefromstring($contents) ?: [0, 0];
        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            throw InvalidProductPhotoException::unreadable();
        }

        $manager = new ImageManager(new GdDriver);
        $image = $manager->read($contents);
        $image->scaleDown(width: self::MAX_IMAGE_DIMENSION, height: self::MAX_IMAGE_DIMENSION);
        $encoded = $image->toWebp(quality: self::WEBP_QUALITY);

        $disk = $this->disk();
        $path = sprintf('products/%s/%s.%s', $restaurantUuid, $productUuid, self::OUTPUT_EXTENSION);

        $this->deletePrevious($disk, $previousImageSrc, $path);

        $disk->put($path, (string) $encoded, 'public');

        return $disk->url($path);
    }

    private function disk(): FilesystemAdapter
    {
        $diskName = config('product_photos.disk') ?? 'public';

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($diskName);

        return $disk;
    }

    /**
     * Remove the previously stored file to avoid orphans, when it is a managed file on this
     * disk and differs from the new path (e.g. a different extension).
     */
    private function deletePrevious(FilesystemAdapter $disk, ?string $previousImageSrc, string $newPath): void
    {
        if ($previousImageSrc === null || $previousImageSrc === '') {
            return;
        }

        $base = rtrim($disk->url(''), '/').'/';
        if (! str_starts_with($previousImageSrc, $base)) {
            return; // external/unmanaged URL — leave it alone.
        }

        $previousPath = substr($previousImageSrc, strlen($base));
        if ($previousPath !== '' && $previousPath !== $newPath && $disk->exists($previousPath)) {
            $disk->delete($previousPath);
        }
    }
}
