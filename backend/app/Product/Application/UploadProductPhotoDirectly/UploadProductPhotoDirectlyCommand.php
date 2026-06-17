<?php

declare(strict_types=1);

namespace App\Product\Application\UploadProductPhotoDirectly;

final readonly class UploadProductPhotoDirectlyCommand
{
    public function __construct(
        public string $productId,
        public string $restaurantId,
        public string $temporaryPath,
    ) {}
}
