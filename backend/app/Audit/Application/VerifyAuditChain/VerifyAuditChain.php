<?php

declare(strict_types=1);

namespace App\Audit\Application\VerifyAuditChain;

use App\Audit\Domain\AuditChainHasher;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Audit\Domain\Interfaces\VerifyChainResultRepositoryInterface;
use App\Audit\Domain\ValueObject\VerifyChainResult;
use App\Shared\Domain\ValueObject\Uuid;

final class VerifyAuditChain
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogRepository,
        private readonly AuditChainHasher $hasher,
        private readonly VerifyChainResultRepositoryInterface $verifyResultRepository,
    ) {}

    public function __invoke(VerifyAuditChainCommand $command): VerifyAuditChainResponse
    {
        $restaurantUuid = Uuid::create($command->restaurantId);
        $events = $this->auditLogRepository->findAllByRestaurantOrdered($restaurantUuid);

        $total = count($events);
        $verified = 0;
        $broken = [];
        $firstBrokenIndex = null;
        $prevHash = null;

        foreach ($events as $index => $event) {
            $expected = $this->hasher->compute(
                prevHash: $prevHash,
                uuid: $event->uuid()->value(),
                restaurantUuid: $event->restaurantId()->value(),
                createdAtIso: $event->createdAt()->format('Y-m-d H:i:s'),
                actionSlug: $event->action()->value(),
                entityType: $event->entityType(),
                entityId: $event->entityId(),
                userUuid: $event->userId()?->value(),
                summary: $event->summary(),
                metadata: $event->metadata(),
                before: $event->before(),
                after: $event->after(),
            );

            if ($expected === $event->integrityHash()) {
                $verified++;
            } else {
                $broken[] = [
                    'uuid' => $event->uuid()->value(),
                    'expected_hash' => $expected,
                    'actual_hash' => $event->integrityHash(),
                ];
                if ($firstBrokenIndex === null) {
                    $firstBrokenIndex = $index;
                }
            }

            $prevHash = $event->integrityHash();
        }

        $result = VerifyChainResult::create(
            restaurantId: $restaurantUuid,
            isValid: $firstBrokenIndex === null,
            totalEvents: $total,
            verifiedCount: $verified,
            brokenEvents: $broken,
            firstBrokenIndex: $firstBrokenIndex,
            verifiedAt: new \DateTimeImmutable,
        );

        $this->verifyResultRepository->save($result);

        return VerifyAuditChainResponse::create(
            totalEvents: $total,
            verifiedCount: $verified,
            brokenEvents: $broken,
            firstBrokenIndex: $firstBrokenIndex,
            isValid: $firstBrokenIndex === null,
        );
    }
}
