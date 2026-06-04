<?php

declare(strict_types=1);

namespace App\Audit\Application\GetArchivedAuditStats;

use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\ArchivedAuditStats;
use App\Shared\Domain\ValueObject\Uuid;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class GetArchivedAuditStats
{
    private const CACHE_TTL_SECONDS = 300;
    private const CACHE_KEY_PREFIX = 'audit:archived-stats:';

    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
        private readonly CacheRepository $cache,
    ) {}

    public function __invoke(GetArchivedAuditStatsCommand $command): GetArchivedAuditStatsResponse
    {
        $restaurantId = Uuid::create($command->restaurantId);
        $dateFrom = $this->parseDate($command->dateFrom);
        $dateTo = $this->parseDate($command->dateTo)?->setTime(23, 59, 59);

        $stats = $this->cache->remember(
            self::cacheKey($restaurantId, $command->dateFrom, $command->dateTo),
            self::CACHE_TTL_SECONDS,
            fn (): ArchivedAuditStats => $this->repository->getArchivedStats($restaurantId, $dateFrom, $dateTo),
        );

        return GetArchivedAuditStatsResponse::create($stats);
    }

    public static function cacheKey(Uuid $restaurantId, ?string $dateFrom = null, ?string $dateTo = null): string
    {
        return self::CACHE_KEY_PREFIX.$restaurantId->value()
            .':'.($dateFrom ?? 'all')
            .':'.($dateTo ?? 'all');
    }

    public static function cacheKeyPrefix(Uuid $restaurantId): string
    {
        return self::CACHE_KEY_PREFIX.$restaurantId->value().':';
    }

    private function parseDate(?string $iso): ?\DateTimeImmutable
    {
        if ($iso === null || $iso === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($iso);
        } catch (\Throwable) {
            return null;
        }
    }
}
