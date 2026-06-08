<?php

namespace App\Product\Application\GetProductPhotoUploadContext;

final readonly class GetProductPhotoUploadContextCommand
{
    public function __construct(
        public string $token,
    ) {}
}
