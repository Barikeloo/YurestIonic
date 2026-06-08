<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Storage;

use App\Product\Domain\Exception\InvalidProductPhotoException;
use App\Product\Domain\Interfaces\ProductPhotoStorageInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

/**
 * Stores product photos on a configured Laravel filesystem disk (local/public in dev,
 * s3 → Cloudflare R2 in production). Returns the public URL to persist in image_src.
 *
 * NOTE (phase 1): the file is stored as received. Resizing/recompression with Intervention
 * Image is added in phase 2 — at which point the extension is normalised to a single format.
 */
class FilesystemProductPhotoStorage implements ProductPhotoStorageInterface
{
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

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

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
        $extension = self::MIME_EXTENSIONS[$mime] ?? null;
        if ($extension === null) {
            throw InvalidProductPhotoException::unreadable();
        }

        $disk = $this->disk();
        $path = sprintf('products/%s/%s.%s', $restaurantUuid, $productUuid, $extension);

        $this->deletePrevious($disk, $previousImageSrc, $path);

        $disk->put($path, $contents, 'public');

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
