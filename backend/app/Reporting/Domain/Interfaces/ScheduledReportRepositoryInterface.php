<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Interfaces;

interface ScheduledReportRepositoryInterface
{
    public function save(array $report): string;

    public function update(string $uuid, array $data): void;

    /**
     * @return array<string, mixed>|null
     */
    public function findByUuid(int $restaurantId, string $uuid): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForRestaurant(int $restaurantId): array;

    public function delete(string $uuid): void;

    public function setActive(string $uuid, bool $active): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDue(\DateTimeImmutable $now): array;

    public function markRun(string $uuid, \DateTimeImmutable $lastRun, \DateTimeImmutable $nextRun): void;
}
