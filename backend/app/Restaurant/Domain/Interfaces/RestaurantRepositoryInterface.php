<?php

namespace App\Restaurant\Domain\Interfaces;

use App\Restaurant\Application\DTO\RestaurantWithInternalId;
use App\Restaurant\Domain\Entity\Restaurant;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;

interface RestaurantRepositoryInterface
{
    public function save(Restaurant $restaurant): void;

    public function all(): array;

    public function getById(string $id): ?Restaurant;

    public function findById(Uuid $id): ?Restaurant;

    public function findByEmail(Email $email): ?Restaurant;

    public function findByUuid(Uuid $uuid): ?Restaurant;

    public function findByInternalId(int $internalId): ?Restaurant;

    public function findByInternalIdWithInternalId(int $internalId): ?RestaurantWithInternalId;

    public function findByUuidWithInternalId(Uuid $uuid): ?RestaurantWithInternalId;

    public function findByTaxId(string $taxId): array;

    public function getKpisByUuid(Uuid $uuid): array;

    public function delete(Uuid $id): void;
}
