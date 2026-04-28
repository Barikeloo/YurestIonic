<?php

declare(strict_types=1);

namespace App\Cash\Application\CancelClosingCashSession;

use App\Cash\Domain\Entity\CashSession;

final readonly class CancelClosingCashSessionResponse
{
    private function __construct(
        public string $id,
        public string $status,
    ) {}

    public static function create(CashSession $cashSession): self
    {
        return new self(
            id: $cashSession->id()->value(),
            status: $cashSession->status()->value(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
        ];
    }
}
