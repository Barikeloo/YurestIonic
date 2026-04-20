<?php

namespace App\Restaurant\Domain\Interfaces;

use App\Restaurant\Application\DTO\RestaurantWithInternalId;
use App\Restaurant\Domain\Entity\Restaurant;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;

interface RestaurantRepositoryInterface
{
    public function save(Restaurant $restaurant): void;

    /**
     * @return array<Restaurant>
     */
    public function all(): array;

    public function getById(string $id): ?Restaurant;

    public function findById(Uuid $id): ?Restaurant;

    public function findByEmail(Email $email): ?Restaurant;

    public function findByUuid(Uuid $uuid): ?Restaurant;

    /**
     * Busca un restaurante por su id interno (BIGINT).
     */
    public function findByInternalId(int $internalId): ?Restaurant;

    /**
     * Busca un restaurante por su id interno y devuelve DTO con entidad e ID interno.
     */
    public function findByInternalIdWithInternalId(int $internalId): ?RestaurantWithInternalId;

    /**
     * Busca un restaurante por UUID y devuelve DTO con entidad e ID interno.
     */
    public function findByUuidWithInternalId(Uuid $uuid): ?RestaurantWithInternalId;

    /**
     * @return array<Restaurant>
     */
    public function findByTaxId(string $taxId): array;

    /**
     * @return array{users: int, zones: int, products: int}
     */
    public function getKpisByUuid(Uuid $uuid): array;

    public function delete(Uuid $id): void;
}
