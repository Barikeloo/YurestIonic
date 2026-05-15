<?php

namespace App\ProductVariant\Domain\Interfaces;

use App\ProductVariant\Domain\Entity\ProductVariant;

interface ProductVariantRepositoryInterface
{
    public function save(ProductVariant $variant): void;

    public function findById(string $id): ?ProductVariant;

    /**
     * @return ProductVariant[]
     */
    public function findByProductId(string $productId): array;

    public function deleteById(string $id): bool;
}
