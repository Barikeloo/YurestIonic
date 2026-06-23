<?php

declare(strict_types=1);

namespace App\Printer\Domain\Interfaces;

use App\Printer\Domain\Entity\PrinterConfig;

interface PrinterConfigRepositoryInterface
{
    public function findByUuid(string $uuid): ?PrinterConfig;

    public function findByZoneUuid(string $zoneUuid): ?PrinterConfig;

    public function findDefaultForRestaurant(int $restaurantId): ?PrinterConfig;

    public function findAllForRestaurant(int $restaurantId): array;

    public function save(PrinterConfig $config): void;

    public function delete(PrinterConfig $config): void;
}
