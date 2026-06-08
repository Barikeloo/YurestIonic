<?php

namespace App\Product\Application\UploadProductPhoto;

final readonly class UploadProductPhotoResponse
{
    private function __construct(
        public string $productName,
        public string $imageSrc,
    ) {}

    public static function create(
        string $productName,
        string $imageSrc,
    ): self {
        return new self(
            productName: $productName,
            imageSrc: $imageSrc,
        );
    }

    public function toArray(): array
    {
        return [
            'product_name' => $this->productName,
            'image_src' => $this->imageSrc,
        ];
    }
}
