<?php

namespace App\Product\Application\SetProductActive;

use App\Product\Domain\Interfaces\ProductRepositoryInterface;

class SetProductActive
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(string $id, bool $active): ?SetProductActiveResponse
    {
        $product = $this->productRepository->findById($id);

        if ($product === null) {
            return null;
        }

        if ($active) {
            $product->activate();
        } else {
            $product->deactivate();
        }

        $this->productRepository->save($product);

        return SetProductActiveResponse::create($product);
    }
}
