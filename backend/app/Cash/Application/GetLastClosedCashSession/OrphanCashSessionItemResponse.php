<?php

declare(strict_types=1);

namespace App\Cash\Application\GetLastClosedCashSession;

final readonly class OrphanCashSessionItemResponse
{
    private function __construct(
        public string $id,
        public string $openedByUserId,
        public string $openedAt,
        public string $deviceId,
    ) {}

    public static function create(
        string $id,
        string $openedByUserId,
        string $openedAt,
        string $deviceId,
    ): self {
        return new self(
            id: $id,
            openedByUserId: $openedByUserId,
            openedAt: $openedAt,
            deviceId: $deviceId,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'opened_by_user_id' => $this->openedByUserId,
            'opened_at' => $this->openedAt,
            'device_id' => $this->deviceId,
        ];
    }
}
