<?php

namespace App\Product\Application\GenerateProductPhotoUploadToken;

final readonly class GenerateProductPhotoUploadTokenCommand
{
    public function __construct(
        public string $productId,
        public string $restaurantId,
        public int $ttlMinutes,
        public string $uploadBaseUrl,
    ) {}
}
