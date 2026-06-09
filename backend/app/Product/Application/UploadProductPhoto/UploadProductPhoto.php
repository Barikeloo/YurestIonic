<?php

namespace App\Product\Application\UploadProductPhoto;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenExpiredException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenNotFoundException;
use App\Product\Domain\Interfaces\ProductPhotoStorageInterface;
use App\Product\Domain\Interfaces\ProductPhotoUploadNotifierInterface;
use App\Product\Domain\Interfaces\ProductPhotoUploadTokenRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductImageSrc;

class UploadProductPhoto
{
    public function __construct(
        private ProductPhotoUploadTokenRepositoryInterface $tokenRepository,
        private ProductRepositoryInterface $productRepository,
        private ProductPhotoStorageInterface $storage,
        private ProductPhotoUploadNotifierInterface $notifier,
        private AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(UploadProductPhotoCommand $command): UploadProductPhotoResponse
    {
        $token = $this->tokenRepository->findByToken($command->token)
            ?? throw ProductPhotoUploadTokenNotFoundException::withToken($command->token);

        if ($token->isExpired()) {
            throw ProductPhotoUploadTokenExpiredException::withToken($command->token);
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

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $token->restaurantId(),
            slug: ActionSlug::create('product.photo_updated'),
            entityType: 'product',
            entityId: $token->productId()->value(),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'product_name' => $product->name()->value(),
            ],
        ));

        $this->notifier->uploaded($token->token(), $token->productId()->value(), $imageUrl);

        return UploadProductPhotoResponse::create(
            productName: $product->name()->value(),
            imageSrc: $imageUrl,
        );
    }
}
