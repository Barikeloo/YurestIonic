<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Entity;

use App\GuestOrder\Domain\Event\TableQrTokenGenerated;
use App\GuestOrder\Domain\ValueObject\QrToken;
use App\Shared\Domain\Event\RecordsEvents;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class TableQrToken
{
    use RecordsEvents;

    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $tableId,
        private readonly Uuid $restaurantId,
        private QrToken $token,
        private int $catalogVersion,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(Uuid $tableId, Uuid $restaurantId): self
    {
        $now = DomainDateTime::now();

        $qrToken = new self(
            id: Uuid::generate(),
            tableId: $tableId,
            restaurantId: $restaurantId,
            token: QrToken::generate(),
            catalogVersion: 1,
            createdAt: $now,
            updatedAt: $now,
        );

        $qrToken->recordEvent(new TableQrTokenGenerated(
            tableQrTokenId: $qrToken->id->value(),
            tableId: $tableId->value(),
            restaurantId: $restaurantId->value(),
            token: $qrToken->token->value(),
        ));

        return $qrToken;
    }

    public static function fromPersistence(
        string $id,
        string $tableId,
        string $restaurantId,
        string $token,
        int $catalogVersion,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            tableId: Uuid::create($tableId),
            restaurantId: Uuid::create($restaurantId),
            token: QrToken::create($token),
            catalogVersion: $catalogVersion,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function regenerate(): void
    {
        $this->token = QrToken::generate();
        $this->touch();

        $this->recordEvent(new TableQrTokenGenerated(
            tableQrTokenId: $this->id->value(),
            tableId: $this->tableId->value(),
            restaurantId: $this->restaurantId->value(),
            token: $this->token->value(),
        ));
    }

    public function incrementCatalogVersion(): void
    {
        $this->catalogVersion++;
        $this->touch();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function tableId(): Uuid
    {
        return $this->tableId;
    }

    public function restaurantId(): Uuid
    {
        return $this->restaurantId;
    }

    public function token(): QrToken
    {
        return $this->token;
    }

    public function catalogVersion(): int
    {
        return $this->catalogVersion;
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
