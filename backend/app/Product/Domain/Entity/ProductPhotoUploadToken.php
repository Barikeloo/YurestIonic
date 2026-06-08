<?php

namespace App\Product\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class ProductPhotoUploadToken
{
    private function __construct(
        private Uuid $id,
        private string $token,
        private Uuid $productId,
        private Uuid $restaurantId,
        private DomainDateTime $expiresAt,
        private ?DomainDateTime $usedAt,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $productId,
        Uuid $restaurantId,
        int $ttlMinutes,
    ): self {
        $now = DomainDateTime::now();
        $expiresAt = DomainDateTime::create(
            $now->value()->add(new \DateInterval('PT'.$ttlMinutes.'M')),
        );

        return new self(
            id: Uuid::generate(),
            token: bin2hex(random_bytes(32)),
            productId: $productId,
            restaurantId: $restaurantId,
            expiresAt: $expiresAt,
            usedAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $token,
        string $productId,
        string $restaurantId,
        \DateTimeImmutable $expiresAt,
        ?\DateTimeImmutable $usedAt,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            token: $token,
            productId: Uuid::create($productId),
            restaurantId: Uuid::create($restaurantId),
            expiresAt: DomainDateTime::create($expiresAt),
            usedAt: $usedAt !== null ? DomainDateTime::create($usedAt) : null,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function isExpired(?DomainDateTime $now = null): bool
    {
        $reference = $now ?? DomainDateTime::now();

        return $this->expiresAt->value() <= $reference->value();
    }

    public function isUsable(?DomainDateTime $now = null): bool
    {
        return ! $this->isUsed() && ! $this->isExpired($now);
    }

    public function markUsed(): void
    {
        $this->usedAt = DomainDateTime::now();
        $this->touch();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function productId(): Uuid
    {
        return $this->productId;
    }

    public function restaurantId(): Uuid
    {
        return $this->restaurantId;
    }

    public function expiresAt(): DomainDateTime
    {
        return $this->expiresAt;
    }

    public function usedAt(): ?DomainDateTime
    {
        return $this->usedAt;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = DomainDateTime::now();
    }
}
