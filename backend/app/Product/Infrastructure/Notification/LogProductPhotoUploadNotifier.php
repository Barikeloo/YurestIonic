<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Notification;

use App\Product\Domain\Interfaces\ProductPhotoUploadNotifierInterface;
use Illuminate\Support\Facades\Log;

/**
 * Phase 1 implementation: records the upload so the flow is observable end-to-end while the
 * realtime transport (Reverb) is wired up in phase 3.
 */
class LogProductPhotoUploadNotifier implements ProductPhotoUploadNotifierInterface
{
    public function uploaded(string $token, string $productUuid, string $imageUrl): void
    {
        Log::info('Product photo uploaded', [
            'token' => $token,
            'product_uuid' => $productUuid,
            'image_url' => $imageUrl,
        ]);
    }
}
