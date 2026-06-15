<?php

namespace App\Product\Application\UploadProductPhoto;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenAlreadyUsedException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenExpiredException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenNotFoundException;
use App\Product\Domain\Interfaces\ProductPhotoStorageInterface;
use App\Product\Domain\Interfaces\ProductPhotoUploadNotifierInterface;
use App\Product\Domain\Interfaces\ProductPhotoUploadTokenRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Shared\Application\Event\EventBusInterface;

class UploadProductPhoto
{
    public function __construct(
        private ProductPhotoUploadTokenRepositoryInterface $tokenRepository,
        private ProductRepositoryInterface $productRepository,
        private ProductPhotoStorageInterface $storage,
        private ProductPhotoUploadNotifierInterface $notifier,
        private EventBusInterface $eventBus,
    ) {}

    public function __invoke(UploadProductPhotoCommand $command): UploadProductPhotoResponse
    {
        $token = $this->tokenRepository->findByToken($command->token)
            ?? throw ProductPhotoUploadTokenNotFoundException::withToken($command->token);

        if ($token->isExpired()) {
            throw ProductPhotoUploadTokenExpiredException::withToken($command->token);
        }

        if ($token->isUsed()) {
            throw ProductPhotoUploadTokenAlreadyUsedException::withToken($command->token);
        }

        $product = $this->productRepository->findByIdAndRestaurant(
            $token->productId()->value(),
            $token->restaurantId()->value(),
        ) ?? throw ProductNotFoundException::withId($token->productId()->value());

        $imageUrl = $this->storage->store(
            temporaryPath: $command->temporaryPath,
            restaurantUuid: $token->restaurantId()->value(),
            productUuid: $token->productId()->value(),
            previousImageSrc: $product->imageSrc()->value(),
        );

        $product->changeImage(ProductImageSrc::create($imageUrl));
        $this->productRepository->save($product);

        $token->markUsed();
        $this->tokenRepository->markAsUsed($token);

        $this->eventBus->publish(...$product->pullDomainEvents());

        $this->notifier->uploaded($token->token(), $token->productId()->value(), $imageUrl);

        return UploadProductPhotoResponse::create(
            productName: $product->name()->value(),
            imageSrc: $imageUrl,
        );
    }
}
