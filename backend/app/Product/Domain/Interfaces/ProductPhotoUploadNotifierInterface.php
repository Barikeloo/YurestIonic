<?php

namespace App\Product\Domain\Interfaces;

interface ProductPhotoUploadNotifierInterface
{
    public function uploaded(string $token, string $productUuid, string $imageUrl): void;
}
