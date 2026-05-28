<?php

declare(strict_types=1);

namespace App\Audit\Application\GetAuditEvent;

use App\Audit\Domain\Exception\AuditLogNotFoundException;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetAuditEvent
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
    ) {}

    public function __invoke(GetAuditEventCommand $command): GetAuditEventResponse
    {
        $log = $this->repository->findByUuid(
            Uuid::create($command->restaurantId),
            Uuid::create($command->uuid),
        ) ?? throw AuditLogNotFoundException::withUuid($command->uuid);

        return GetAuditEventResponse::fromAuditLog($log);
    }
}
