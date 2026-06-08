<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Notification;

use App\Product\Domain\Interfaces\ProductPhotoUploadNotifierInterface;
use App\Product\Infrastructure\Broadcasting\ProductPhotoUploaded;

/**
 * Pushes the upload to the TPV in real time by broadcasting over Laravel Reverb on the
 * `photo-upload.{token}` channel.
 */
class BroadcastProductPhotoUploadNotifier implements ProductPhotoUploadNotifierInterface
{
    public function uploaded(string $token, string $productUuid, string $imageUrl): void
    {
        ProductPhotoUploaded::dispatch($token, $productUuid, $imageUrl);
    }
}
