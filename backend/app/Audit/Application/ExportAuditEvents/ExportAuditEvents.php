<?php

declare(strict_types=1);

namespace App\Audit\Application\ExportAuditEvents;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ListAuditLogsCriteria;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;

final class ExportAuditEvents
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(ExportAuditEventsCommand $command): iterable
    {
        $criteria = new ListAuditLogsCriteria(
            restaurantId: Uuid::create($command->restaurantId),
            category: $command->category,
            severity: $command->severity,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            dateFrom: $this->parseDate($command->dateFrom),
            dateTo: $this->parseDate($command->dateTo)?->setTime(23, 59, 59),
            search: $command->search,
            anomalyOnly: $command->anomalyOnly,
            includeArchived: $command->includeArchived,
        );

        $rowCount = 0;
        foreach ($this->repository->streamForExport($criteria) as $log) {
            yield $log;
            $rowCount++;
        }

        $this->emitExportedEvent($command, $rowCount);
    }

    private function emitExportedEvent(ExportAuditEventsCommand $command, int $rowCount): void
    {
        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('audit.exported'),
            entityType: 'audit_log',
            entityId: $command->restaurantId,
            metadata: [
                'row_count' => $rowCount,
                'format' => $command->format->value,
                'filters' => array_filter([
                    'category' => $command->category,
                    'severity' => $command->severity,
                    'user_id' => $command->userId,
                    'device_id' => $command->deviceId,
                    'date_from' => $command->dateFrom,
                    'date_to' => $command->dateTo,
                    'search' => $command->search,
                    'anomaly_only' => $command->anomalyOnly ? true : null,
                    'include_archived' => $command->includeArchived ? true : null,
                ], static fn ($v) => $v !== null),
            ],
        ));
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
