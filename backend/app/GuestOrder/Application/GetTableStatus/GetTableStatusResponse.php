<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetTableStatus;

use App\GuestOrder\Domain\ReadModel\TableStatusData;

final readonly class GetTableStatusResponse
{
    private function __construct(
        public string $restaurantName,
        public ?string $restaurantLogoUrl,
        public ?string $restaurantPrimaryColor,
        public string $tableName,
        public string $zoneName,
        public string $orderStatus,
        public int $activeSessionsCount,
    ) {}

    public static function create(
        string $restaurantName,
        ?string $restaurantLogoUrl,
        ?string $restaurantPrimaryColor,
        string $tableName,
        string $zoneName,
        string $orderStatus,
        int $activeSessionsCount,
    ): self {
        return new self(
            restaurantName: $restaurantName,
            restaurantLogoUrl: $restaurantLogoUrl,
            restaurantPrimaryColor: $restaurantPrimaryColor,
            tableName: $tableName,
            zoneName: $zoneName,
            orderStatus: $orderStatus,
            activeSessionsCount: $activeSessionsCount,
        );
    }

    public static function fromReadModel(TableStatusData $data): self
    {
        return new self(
            restaurantName: $data->restaurantName,
            restaurantLogoUrl: $data->restaurantLogoUrl,
            restaurantPrimaryColor: $data->restaurantPrimaryColor,
            tableName: $data->tableName,
            zoneName: $data->zoneName,
            orderStatus: $data->orderStatus,
            activeSessionsCount: $data->activeSessionsCount,
        );
    }

    public function toArray(): array
    {
        return [
            'restaurant' => [
                'name'          => $this->restaurantName,
                'logo_url'      => $this->restaurantLogoUrl,
                'primary_color' => $this->restaurantPrimaryColor,
                'locale'        => 'es',
            ],
            'table' => [
                'name' => $this->tableName,
                'zone' => $this->zoneName,
            ],
            'order_status'          => $this->orderStatus,
            'active_sessions_count' => $this->activeSessionsCount,
        ];
    }
}
