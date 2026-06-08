<?php

namespace App\Product\Application\GenerateProductPhotoUploadToken;

final readonly class GenerateProductPhotoUploadTokenResponse
{
    private function __construct(
        public string $token,
        public string $uploadUrl,
        public string $expiresAt,
    ) {}

    public static function create(
        string $token,
        string $uploadUrl,
        string $expiresAt,
    ): self {
        return new self(
            token: $token,
            uploadUrl: $uploadUrl,
            expiresAt: $expiresAt,
        );
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'upload_url' => $this->uploadUrl,
            'expires_at' => $this->expiresAt,
        ];
    }
}
