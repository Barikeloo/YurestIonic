<?php

namespace App\Restaurant\Domain\Interfaces;

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

    public function delete(Uuid $id): void;
}
