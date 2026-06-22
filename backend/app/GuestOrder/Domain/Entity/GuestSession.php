<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Entity;

use App\GuestOrder\Domain\Event\GuestSessionStarted;
use App\GuestOrder\Domain\Event\TableOpenedByGuest;
use App\GuestOrder\Domain\ValueObject\GuestSessionToken;
use App\GuestOrder\Domain\ValueObject\IdentityMode;
use App\Shared\Domain\Event\RecordsEvents;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class GuestSession
{
    use RecordsEvents;

    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $tableQrTokenId,
        private ?Uuid $orderId,
        private readonly Uuid $restaurantId,
        private readonly GuestSessionToken $sessionToken,
        private readonly IdentityMode $identityMode,
        private readonly ?string $guestName,
        private readonly bool $openedTable,
        private readonly ?int $dinersCount,
        private ?DomainDateTime $checkRequestedAt,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private readonly DomainDateTime $expiresAt,
    ) {}

    public static function dddCreateAsTableOpener(
        Uuid $tableQrTokenId,
        Uuid $restaurantId,
        Uuid $orderId,
        GuestSessionToken $sessionToken,
        IdentityMode $identityMode,
        ?string $guestName,
        int $dinersCount,
    ): self {
        $now    = DomainDateTime::now();
        $expiry = DomainDateTime::create(
            new \DateTimeImmutable('+24 hours'),
        );

        $session = new self(
            id: Uuid::generate(),
            tableQrTokenId: $tableQrTokenId,
            orderId: $orderId,
            restaurantId: $restaurantId,
            sessionToken: $sessionToken,
            identityMode: $identityMode,
            guestName: $guestName,
            openedTable: true,
            dinersCount: $dinersCount,
            checkRequestedAt: null,
            createdAt: $now,
            updatedAt: $now,
            expiresAt: $expiry,
        );

        $session->recordEvent(new TableOpenedByGuest(
            guestSessionId: $session->id->value(),
            tableQrTokenId: $tableQrTokenId->value(),
            restaurantId: $restaurantId->value(),
            orderId: $orderId->value(),
            guestName: $guestName,
            dinersCount: $dinersCount,
        ));

        return $session;
    }

    public static function dddCreateAsJoiner(
        Uuid $tableQrTokenId,
        Uuid $restaurantId,
        Uuid $orderId,
        GuestSessionToken $sessionToken,
        IdentityMode $identityMode,
        ?string $guestName,
    ): self {
        $now    = DomainDateTime::now();
        $expiry = DomainDateTime::create(
            new \DateTimeImmutable('+24 hours'),
        );

        $session = new self(
            id: Uuid::generate(),
            tableQrTokenId: $tableQrTokenId,
            orderId: $orderId,
            restaurantId: $restaurantId,
            sessionToken: $sessionToken,
            identityMode: $identityMode,
            guestName: $guestName,
            openedTable: false,
            dinersCount: null,
            checkRequestedAt: null,
            createdAt: $now,
            updatedAt: $now,
            expiresAt: $expiry,
        );

        $session->recordEvent(new GuestSessionStarted(
            guestSessionId: $session->id->value(),
            tableQrTokenId: $tableQrTokenId->value(),
            restaurantId: $restaurantId->value(),
            orderId: $orderId->value(),
            guestName: $guestName,
        ));

        return $session;
    }

    public static function fromPersistence(
        string $id,
        string $tableQrTokenId,
        ?string $orderId,
        string $restaurantId,
        string $sessionToken,
        string $identityMode,
        ?string $guestName,
        bool $openedTable,
        ?int $dinersCount,
        ?\DateTimeImmutable $checkRequestedAt,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        \DateTimeImmutable $expiresAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            tableQrTokenId: Uuid::create($tableQrTokenId),
            orderId: $orderId !== null ? Uuid::create($orderId) : null,
            restaurantId: Uuid::create($restaurantId),
            sessionToken: GuestSessionToken::create($sessionToken),
            identityMode: IdentityMode::create($identityMode),
            guestName: $guestName,
            openedTable: $openedTable,
            dinersCount: $dinersCount,
            checkRequestedAt: $checkRequestedAt !== null ? DomainDateTime::create($checkRequestedAt) : null,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
            expiresAt: DomainDateTime::create($expiresAt),
        );
    }

    public function linkToOrder(Uuid $orderId): void
    {
        $this->orderId  = $orderId;
        $this->updatedAt = DomainDateTime::now();
    }

    public function isExpired(): bool
    {
        return $this->expiresAt->value() < new \DateTimeImmutable();
    }

    public function expireNow(): void
    {
        $this->updatedAt = DomainDateTime::now();
    }

    public function id(): Uuid { return $this->id; }
    public function tableQrTokenId(): Uuid { return $this->tableQrTokenId; }
    public function orderId(): ?Uuid { return $this->orderId; }
    public function restaurantId(): Uuid { return $this->restaurantId; }
    public function sessionToken(): GuestSessionToken { return $this->sessionToken; }
    public function identityMode(): IdentityMode { return $this->identityMode; }
    public function guestName(): ?string { return $this->guestName; }
    public function openedTable(): bool { return $this->openedTable; }
    public function dinersCount(): ?int { return $this->dinersCount; }
    public function checkRequestedAt(): ?DomainDateTime { return $this->checkRequestedAt; }
    public function createdAt(): DomainDateTime { return $this->createdAt; }
    public function updatedAt(): DomainDateTime { return $this->updatedAt; }
    public function expiresAt(): DomainDateTime { return $this->expiresAt; }
}
