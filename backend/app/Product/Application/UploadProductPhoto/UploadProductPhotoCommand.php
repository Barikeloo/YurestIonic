<?php

namespace App\Product\Application\UploadProductPhoto;

final readonly class UploadProductPhotoCommand
{
    public function __construct(
        public string $token,
        public string $temporaryPath,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
