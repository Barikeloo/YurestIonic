<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\ReadModel;

final readonly class TableStatusData
{
    public function __construct(
        public string $restaurantName,
        public ?string $restaurantLogoUrl,
        public ?string $restaurantPrimaryColor,
        public string $tableName,
        public string $zoneName,
        public string $orderStatus,        // 'none' | 'open' | 'to_charge'
        public int $activeSessionsCount,
    ) {}
}
