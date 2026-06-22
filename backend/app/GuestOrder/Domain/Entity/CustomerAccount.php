<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class CustomerAccount
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly string $name,
        private readonly string $email,
        private string $passwordHash,
        private int $points,
        private int $totalSpentCents,
        private int $visitsCount,
        private ?DomainDateTime $lastVisitAt,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $restaurantId,
        string $name,
        string $email,
        string $passwordHash,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            id: Uuid::generate(),
            restaurantId: $restaurantId,
            name: $name,
            email: strtolower(trim($email)),
            passwordHash: $passwordHash,
            points: 0,
            totalSpentCents: 0,
            visitsCount: 0,
            lastVisitAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $name,
        string $email,
        string $passwordHash,
        int $points,
        int $totalSpentCents,
        int $visitsCount,
        ?\DateTimeImmutable $lastVisitAt,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            restaurantId: Uuid::create($restaurantId),
            name: $name,
            email: $email,
            passwordHash: $passwordHash,
            points: $points,
            totalSpentCents: $totalSpentCents,
            visitsCount: $visitsCount,
            lastVisitAt: $lastVisitAt !== null ? DomainDateTime::create($lastVisitAt) : null,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function verifyPassword(string $plain): bool
    {
        return password_verify($plain, $this->passwordHash);
    }

    public function creditVisit(int $amountCents): void
    {
        $this->points         += (int) floor($amountCents / 100);
        $this->totalSpentCents += $amountCents;
        $this->visitsCount++;
        $this->lastVisitAt = DomainDateTime::now();
        $this->updatedAt   = DomainDateTime::now();
    }

    public function generateAuthToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function id(): Uuid { return $this->id; }
    public function restaurantId(): Uuid { return $this->restaurantId; }
    public function name(): string { return $this->name; }
    public function email(): string { return $this->email; }
    public function passwordHash(): string { return $this->passwordHash; }
    public function points(): int { return $this->points; }
    public function totalSpentCents(): int { return $this->totalSpentCents; }
    public function visitsCount(): int { return $this->visitsCount; }
    public function lastVisitAt(): ?DomainDateTime { return $this->lastVisitAt; }
    public function createdAt(): DomainDateTime { return $this->createdAt; }
    public function updatedAt(): DomainDateTime { return $this->updatedAt; }
}
