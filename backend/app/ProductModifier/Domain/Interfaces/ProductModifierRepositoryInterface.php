<?php

namespace App\ProductModifier\Domain\Interfaces;

use App\ProductModifier\Domain\Entity\ProductModifier;

interface ProductModifierRepositoryInterface
{
    public function save(ProductModifier $modifier): void;

    public function findById(string $id): ?ProductModifier;

    /**
     * @return ProductModifier[]
     */
    public function findByProductId(string $productId): array;

    public function deleteById(string $id): bool;
}
