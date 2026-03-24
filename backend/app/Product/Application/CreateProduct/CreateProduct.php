<?php

namespace App\Product\Application\CreateProduct;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\Uuid;

class CreateProduct
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(
        string $familyId,
        string $taxId,
        ?string $imageSrc,
        string $name,
        int $price,
        int $stock,
        bool $active,
    ): CreateProductResponse {
        $product = Product::dddCreate(
            familyId: Uuid::create($familyId),
            taxId: Uuid::create($taxId),
            imageSrc: ProductImageSrc::create($imageSrc),
            name: ProductName::create($name),
            price: ProductPrice::create($price),
            stock: ProductStock::create($stock),
            active: $active,
        );

        $this->productRepository->save($product);

        return CreateProductResponse::create($product);
    }
}
