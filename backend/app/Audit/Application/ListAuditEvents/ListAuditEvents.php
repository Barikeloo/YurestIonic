<?php

declare(strict_types=1);

namespace App\Audit\Application\ListAuditEvents;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Audit\Domain\ListAuditLogsCriteria;
use App\Shared\Domain\ValueObject\Uuid;

final class ListAuditEvents
{
    private const PAGE_LIMIT = 50;

    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
    ) {}

    public function __invoke(ListAuditEventsCommand $command): ListAuditEventsResponse
    {
        [$cursorCreatedAt, $cursorInternalId] = $this->decodeCursor($command->cursor);

        $criteria = new ListAuditLogsCriteria(
            restaurantId: Uuid::create($command->restaurantId),
            category: $command->category,
            severity: $command->severity,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            dateFrom: $this->parseDate($command->dateFrom),
            dateTo: $this->parseDate($command->dateTo),
            search: $command->search,
            anomalyOnly: $command->anomalyOnly,
            cursorCreatedAt: $cursorCreatedAt,
            cursorInternalId: $cursorInternalId,
            sinceUuid: $command->sinceUuid !== null ? Uuid::create($command->sinceUuid) : null,
            limit: self::PAGE_LIMIT,
        );

        $page = $this->repository->list($criteria);

        $items = array_map(
            static fn (AuditLog $log): AuditEventItemResponse => AuditEventItemResponse::create(
                uuid: $log->uuid()->value(),
                entityType: $log->entityType(),
                entityId: $log->entityId(),
                action: $log->action()->value(),
                category: $log->category()->value(),
                severity: $log->severity()->value(),
                summary: $log->summary(),
                reason: $log->reason(),
                sessionId: $log->sessionId()?->value(),
                anomalyKind: $log->anomalyKind(),
                integrityHash: $log->integrityHash(),
                prevHash: $log->prevHash(),
                metadata: $log->metadata(),
                userId: $log->userId()?->value(),
                before: $log->before(),
                after: $log->after(),
                ipAddress: $log->ipAddress(),
                deviceId: $log->deviceId(),
                createdAt: $log->createdAt()->format('Y-m-d H:i:s'),
            ),
            $page->items,
        );

        return ListAuditEventsResponse::create(
            items: $items,
            nextCursor: $this->encodeCursor($page->nextCursorCreatedAt, $page->nextCursorInternalId),
            hasMore: $page->hasMore,
        );
    }

    /**
     * @return array{0: ?\DateTimeImmutable, 1: ?int}
     */
    private function decodeCursor(?string $cursor): array
    {
        if ($cursor === null || $cursor === '') {
            return [null, null];
        }

        $decoded = base64_decode($cursor, true);
        if ($decoded === false) {
            return [null, null];
        }

        $data = json_decode($decoded, true);
        if (! is_array($data) || ! isset($data['c'], $data['i'])) {
            return [null, null];
        }

        try {
            $createdAt = new \DateTimeImmutable((string) $data['c']);
        } catch (\Throwable) {
            return [null, null];
        }

        return [$createdAt, (int) $data['i']];
    }

    private function encodeCursor(?\DateTimeImmutable $createdAt, ?int $internalId): ?string
    {
        if ($createdAt === null || $internalId === null) {
            return null;
        }

        return base64_encode(json_encode([
            'c' => $createdAt->format('Y-m-d H:i:s'),
            'i' => $internalId,
        ], JSON_THROW_ON_ERROR));
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
