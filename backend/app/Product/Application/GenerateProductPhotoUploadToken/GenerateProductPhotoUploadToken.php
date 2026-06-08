<?php

namespace App\Product\Application\GenerateProductPhotoUploadToken;

use App\Product\Domain\Entity\ProductPhotoUploadToken;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductPhotoUploadTokenRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class GenerateProductPhotoUploadToken
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductPhotoUploadTokenRepositoryInterface $tokenRepository,
    ) {}

    public function __invoke(GenerateProductPhotoUploadTokenCommand $command): GenerateProductPhotoUploadTokenResponse
    {
        $product = $this->productRepository->findByIdAndRestaurant($command->productId, $command->restaurantId)
            ?? throw ProductNotFoundException::withId($command->productId);

        $token = ProductPhotoUploadToken::dddCreate(
            productId: $product->id(),
            restaurantId: Uuid::create($command->restaurantId),
            ttlMinutes: $command->ttlMinutes,
        );

        $this->tokenRepository->save($token);

        $uploadUrl = rtrim($command->uploadBaseUrl, '/').'/u/foto/'.$token->token();

        return GenerateProductPhotoUploadTokenResponse::create(
            token: $token->token(),
            uploadUrl: $uploadUrl,
            expiresAt: $token->expiresAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
