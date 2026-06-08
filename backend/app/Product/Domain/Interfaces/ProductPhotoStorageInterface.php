<?php

namespace App\Product\Domain\Interfaces;

/**
 * Port for persisting a product photo to a storage backend (local disk, S3/R2, ...).
 *
 * The Application layer only knows about this contract: it hands over the path of an
 * already-uploaded temporary file plus tenant/product context, and receives back the
 * public reference (URL or relative path) to store in `products.image_src`.
 */
interface ProductPhotoStorageInterface
{
    /**
     * Store (and process) the photo located at $temporaryPath, returning the public
     * reference to persist in the product. Implementations may resize/recompress and
     * should remove $previousImageSrc when it is a managed file, to avoid orphans.
     */
    public function store(
        string $temporaryPath,
        string $restaurantUuid,
        string $productUuid,
        ?string $previousImageSrc,
    ): string;
}
