<?php

namespace App\Product\Application\GetProductPhotoUploadContext;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenAlreadyUsedException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenExpiredException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenNotFoundException;
use App\Product\Domain\Interfaces\ProductPhotoUploadTokenRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class GetProductPhotoUploadContext
{
    public function __construct(
        private ProductPhotoUploadTokenRepositoryInterface $tokenRepository,
        private ProductRepositoryInterface $productRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(GetProductPhotoUploadContextCommand $command): GetProductPhotoUploadContextResponse
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

        $restaurant = $this->restaurantRepository->findById(Uuid::create($token->restaurantId()->value()));

        return GetProductPhotoUploadContextResponse::create(
            productName: $product->name()->value(),
            imageSrc: $product->imageSrc()->value(),
            expiresAt: $token->expiresAt()->format(\DateTimeInterface::ATOM),
            restaurantName: $restaurant?->name()->value() ?? '',
        );
    }
}
