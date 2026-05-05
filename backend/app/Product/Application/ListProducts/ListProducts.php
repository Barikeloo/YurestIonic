<?php

declare(strict_types=1);

namespace App\Product\Application\ListProducts;

use App\Product\Domain\Interfaces\ProductRepositoryInterface;

class ListProducts
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(bool $includeDeleted = false, bool $onlyActive = false): array
    {
        $products = $this->productRepository->findAll($includeDeleted);

        if ($onlyActive) {
            $products = array_filter($products, fn ($p) => $p->isActive());
        }

        return array_map(
            static fn ($product): array => ListProductsItemResponse::create($product)->toArray(),
            $products,
        );
    }
}
