<?php

declare(strict_types=1);

namespace App\AuditSavedView\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

final class AuditSavedView
{
    /**
     * @param array<string, mixed> $filters
     */
    private function __construct(
        private readonly Uuid $uuid,
        private readonly Uuid $restaurantId,
        private readonly Uuid $userId,
        private readonly string $name,
        private readonly ?string $icon,
        private readonly array $filters,
        private readonly DomainDateTime $createdAt,
        private readonly DomainDateTime $updatedAt,
    ) {}

    /**
     * @param array<string, mixed> $filters
     */
    public static function dddCreate(
        Uuid $uuid,
        Uuid $restaurantId,
        Uuid $userId,
        string $name,
        ?string $icon,
        array $filters,
        ?DomainDateTime $createdAt = null,
        ?DomainDateTime $updatedAt = null,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            uuid: $uuid,
            restaurantId: $restaurantId,
            userId: $userId,
            name: $name,
            icon: $icon,
            filters: $filters,
            createdAt: $createdAt ?? $now,
            updatedAt: $updatedAt ?? $now,
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    public static function fromPersistence(
        string $uuid,
        string $restaurantId,
        string $userId,
        string $name,
        ?string $icon,
        array $filters,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            uuid: Uuid::create($uuid),
            restaurantId: Uuid::create($restaurantId),
            userId: Uuid::create($userId),
            name: $name,
            icon: $icon,
            filters: $filters,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function restaurantId(): Uuid
    {
        return $this->restaurantId;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function icon(): ?string
    {
        return $this->icon;
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return $this->filters;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function withUpdatedFilters(array $filters, DomainDateTime $updatedAt): self
    {
        return new self(
            uuid: $this->uuid,
            restaurantId: $this->restaurantId,
            userId: $this->userId,
            name: $this->name,
            icon: $this->icon,
            filters: $filters,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function withUpdatedName(string $name, DomainDateTime $updatedAt): self
    {
        return new self(
            uuid: $this->uuid,
            restaurantId: $this->restaurantId,
            userId: $this->userId,
            name: $name,
            icon: $this->icon,
            filters: $this->filters,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
        );
    }
}
