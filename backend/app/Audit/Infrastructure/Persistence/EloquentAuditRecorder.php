<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Persistence;

use App\Audit\Domain\AnomalyDetector;
use App\Audit\Domain\AuditChainHasher;
use App\Audit\Domain\AuditEventCatalog;
use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use Illuminate\Support\Facades\DB;

final class EloquentAuditRecorder implements AuditRecorderInterface
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
        private readonly AnomalyDetector $detector,
        private readonly AuditChainHasher $hasher,
    ) {}

    public function record(AuditEventDraft $draft): void
    {
        try {
            DB::transaction(function () use ($draft): void {
                $resolved = AuditEventCatalog::resolve($draft->slug, $draft->toCatalogContext());
                $anomalyKind = $this->detector->detect($draft);

                $prevHash = $this->repository->lockAndGetLastHashForRestaurant($draft->restaurantId);

                $uuid = Uuid::generate();
                $createdAt = DomainDateTime::now();

                $integrityHash = $this->hasher->compute(
                    prevHash: $prevHash,
                    uuid: $uuid->value(),
                    restaurantUuid: $draft->restaurantId->value(),
                    createdAtIso: $createdAt->format('Y-m-d H:i:s'),
                    actionSlug: $draft->slug->value(),
                    entityType: $draft->entityType,
                    entityId: $draft->entityId,
                    userUuid: $draft->userId?->value(),
                    summary: $resolved['summary'],
                    metadata: $draft->metadata,
                    before: $draft->before,
                    after: $draft->after,
                );

                $entity = AuditLog::dddCreate(
                    uuid: $uuid,
                    restaurantId: $draft->restaurantId,
                    entityType: $draft->entityType,
                    entityId: $draft->entityId,
                    action: $draft->slug,
                    category: $resolved['category'],
                    severity: $resolved['severity'],
                    summary: $resolved['summary'],
                    integrityHash: $integrityHash,
                    prevHash: $prevHash,
                    reason: $draft->reason,
                    sessionId: $draft->sessionId,
                    anomalyKind: $anomalyKind,
                    metadata: $draft->metadata,
                    userId: $draft->userId,
                    before: $draft->before,
                    after: $draft->after,
                    ipAddress: $draft->ipAddress,
                    deviceId: $draft->deviceId,
                    createdAt: $createdAt,
                );

                $this->repository->save($entity);
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
