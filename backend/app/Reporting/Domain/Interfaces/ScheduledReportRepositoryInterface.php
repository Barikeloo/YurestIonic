<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Interfaces;

interface ScheduledReportRepositoryInterface
{
    public function save(array $report): string;

    public function update(string $uuid, array $data): void;

    public function findByUuid(int $restaurantId, string $uuid): ?array;

    public function listForRestaurant(int $restaurantId): array;

    public function delete(string $uuid): void;

    public function setActive(string $uuid, bool $active): void;

    public function listDue(\DateTimeImmutable $now): array;

    public function markRun(string $uuid, \DateTimeImmutable $lastRun, \DateTimeImmutable $nextRun): void;
}
