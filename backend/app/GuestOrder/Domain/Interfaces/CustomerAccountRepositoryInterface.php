<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Interfaces;

use App\GuestOrder\Domain\Entity\CustomerAccount;

interface CustomerAccountRepositoryInterface
{
    public function findByEmailAndRestaurant(string $email, string $restaurantId): ?CustomerAccount;

    public function findById(string $id): ?CustomerAccount;

    public function save(CustomerAccount $account): void;

    public function saveAuthToken(string $accountId, string $token, \DateTimeImmutable $expiresAt): void;

    public function findByAuthToken(string $token): ?CustomerAccount;

    public function invalidateAuthToken(string $token): void;

    /** @return array<array{id:string, title:string, discount_type:string, discount_value:int, min_points:int}> */
    public function getActiveOffers(string $restaurantId, int $customerPoints): array;
}
