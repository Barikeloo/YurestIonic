<?php

declare(strict_types=1);

namespace App\Cash\Application\ListCashMovements;

final readonly class ListCashMovementsItemResponse
{
    private function __construct(
        public string $uuid,
        public string $type,
        public string $reasonCode,
        public int $amountCents,
        public ?string $description,
        public string $userId,
        public string $createdAt,
    ) {}

    public static function create(
        string $uuid,
        string $type,
        string $reasonCode,
        int $amountCents,
        ?string $description,
        string $userId,
        string $createdAt,
    ): self {
        return new self(
            uuid: $uuid,
            type: $type,
            reasonCode: $reasonCode,
            amountCents: $amountCents,
            description: $description,
            userId: $userId,
            createdAt: $createdAt,
        );
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'reason_code' => $this->reasonCode,
            'amount_cents' => $this->amountCents,
            'description' => $this->description,
            'user_id' => $this->userId,
            'created_at' => $this->createdAt,
        ];
    }
}
