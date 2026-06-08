<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Notification;

use App\Product\Domain\Interfaces\ProductPhotoUploadNotifierInterface;
use Illuminate\Support\Facades\Log;

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
