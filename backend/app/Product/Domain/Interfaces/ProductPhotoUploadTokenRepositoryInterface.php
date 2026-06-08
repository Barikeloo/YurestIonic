<?php

namespace App\Product\Domain\Interfaces;

use App\Product\Domain\Entity\ProductPhotoUploadToken;

interface ProductPhotoUploadTokenRepositoryInterface
{
    public function findByToken(string $token): ?ProductPhotoUploadToken;

    public function save(ProductPhotoUploadToken $token): void;

    public function deleteExpired(): int;
}
