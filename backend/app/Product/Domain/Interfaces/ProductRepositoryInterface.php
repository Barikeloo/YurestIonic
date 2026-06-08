<?php

namespace App\Product\Domain\Interfaces;

use App\Product\Domain\Entity\Product;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;

    public function findById(string $id): ?Product;

    /**
     * Find a product by its uuid scoped explicitly to a restaurant uuid, without relying
     * on the ambient tenant context. Used by public (unauthenticated) flows such as the
     * QR photo upload, where the restaurant is derived from a signed token.
     */
    public function findByIdAndRestaurant(string $id, string $restaurantUuid): ?Product;

    public function findAll(bool $includeDeleted = false): array;

    public function deleteById(string $id): bool;
}
