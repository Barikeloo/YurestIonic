<?php

namespace App\Product\Application\GetProductPhotoUploadContext;

final readonly class GetProductPhotoUploadContextResponse
{
    private function __construct(
        public string $productName,
        public ?string $imageSrc,
        public string $expiresAt,
        public string $restaurantName,
    ) {}

    public static function create(
        string $productName,
        ?string $imageSrc,
        string $expiresAt,
        string $restaurantName,
    ): self {
        return new self(
            productName: $productName,
            imageSrc: $imageSrc,
            expiresAt: $expiresAt,
            restaurantName: $restaurantName,
        );
    }

    public function toArray(): array
    {
        return [
            'product_name' => $this->productName,
            'image_src' => $this->imageSrc,
            'expires_at' => $this->expiresAt,
            'restaurant_name' => $this->restaurantName,
        ];
    }
}
