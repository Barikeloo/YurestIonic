<?php

namespace App\Product\Domain\Interfaces;

use App\Product\Domain\Entity\Product;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;

    public function findById(string $id): ?Product;

    /**
     * @return array<int, Product>
     */
    public function findAll(bool $includeDeleted = false): array;

    public function deleteById(string $id): bool;
}
