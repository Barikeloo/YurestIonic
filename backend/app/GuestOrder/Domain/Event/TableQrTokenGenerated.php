<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final readonly class TableQrTokenGenerated implements DomainEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $tableQrTokenId,
        private string $tableId,
        private string $restaurantId,
        private string $token,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function tableQrTokenId(): string
    {
        return $this->tableQrTokenId;
    }

    public function tableId(): string
    {
        return $this->tableId;
    }

    public function restaurantId(): string
    {
        return $this->restaurantId;
    }

    public function token(): string
    {
        return $this->token;
    }
}
