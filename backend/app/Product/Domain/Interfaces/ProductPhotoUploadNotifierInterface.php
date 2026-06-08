<?php

namespace App\Product\Domain\Interfaces;

/**
 * Port for notifying interested listeners (the TPV, in real time) that a product photo
 * has just been uploaded via the QR flow.
 *
 * Phase 1 ships a logging no-op implementation. Phase 3 swaps it for a broadcast over
 * Laravel Reverb on the `photo-upload.{token}` channel.
 */
interface ProductPhotoUploadNotifierInterface
{
    public function uploaded(string $token, string $productUuid, string $imageUrl): void;
}
