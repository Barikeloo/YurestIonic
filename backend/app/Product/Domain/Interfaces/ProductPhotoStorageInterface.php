<?php

namespace App\Product\Domain\Interfaces;

interface ProductPhotoStorageInterface
{

    public function store(
        string $temporaryPath,
        string $restaurantUuid,
        string $productUuid,
        ?string $previousImageSrc,
    ): string;
}
