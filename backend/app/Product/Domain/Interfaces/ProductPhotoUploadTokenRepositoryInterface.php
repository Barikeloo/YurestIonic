<?php

namespace App\Product\Domain\Interfaces;

use App\Product\Domain\Entity\ProductPhotoUploadToken;

interface ProductPhotoUploadTokenRepositoryInterface
{
    public function findByToken(string $token): ?ProductPhotoUploadToken;

    public function save(ProductPhotoUploadToken $token): void;

    /**
     * Atomically marks the token as used. Throws ProductPhotoUploadTokenAlreadyUsedException
     * when another request already marked it (race-condition guard).
     */
    public function markAsUsed(ProductPhotoUploadToken $token): void;

    public function deleteExpired(): int;
}
