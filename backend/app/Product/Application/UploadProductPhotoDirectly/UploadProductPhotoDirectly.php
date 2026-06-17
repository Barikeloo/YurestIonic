<?php

declare(strict_types=1);

namespace App\Product\Application\UploadProductPhotoDirectly;

use App\Product\Application\UploadProductPhoto\UploadProductPhotoResponse;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductPhotoStorageInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Shared\Application\Event\EventBusInterface;

class UploadProductPhotoDirectly
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductPhotoStorageInterface $storage,
        private EventBusInterface $eventBus,
    ) {}

    public function __invoke(UploadProductPhotoDirectlyCommand $command): UploadProductPhotoResponse
    {
        $product = $this->productRepository->findByIdAndRestaurant(
            $command->productId,
            $command->restaurantId,
        ) ?? throw ProductNotFoundException::withId($command->productId);

        $imageUrl = $this->storage->store(
            temporaryPath: $command->temporaryPath,
            restaurantUuid: $command->restaurantId,
            productUuid: $command->productId,
            previousImageSrc: $product->imageSrc()->value(),
        );

        $product->changeImage(ProductImageSrc::create($imageUrl));
        $this->productRepository->save($product);

        $this->eventBus->publish(...$product->pullDomainEvents());

        return UploadProductPhotoResponse::create(
            productName: $product->name()->value(),
            imageSrc: $imageUrl,
        );
    }
}
