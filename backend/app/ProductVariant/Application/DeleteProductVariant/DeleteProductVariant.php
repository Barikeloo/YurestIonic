<?php

namespace App\ProductVariant\Application\DeleteProductVariant;

use App\ProductVariant\Domain\Exception\ProductVariantNotFoundException;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;

class DeleteProductVariant
{
    public function __construct(
        private ProductVariantRepositoryInterface $variantRepository,
    ) {}

    public function __invoke(DeleteProductVariantCommand $command): void
    {
        $variant = $this->variantRepository->findById($command->id)
            ?? throw ProductVariantNotFoundException::withId($command->id);

        $this->variantRepository->deleteById($command->id);
    }
}
