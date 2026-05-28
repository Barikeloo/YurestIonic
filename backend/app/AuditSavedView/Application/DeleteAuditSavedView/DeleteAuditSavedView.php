<?php

declare(strict_types=1);

namespace App\AuditSavedView\Application\DeleteAuditSavedView;

use App\AuditSavedView\Domain\Exception\AuditSavedViewNotFoundException;
use App\AuditSavedView\Domain\Interfaces\AuditSavedViewRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class DeleteAuditSavedView
{
    public function __construct(
        private readonly AuditSavedViewRepositoryInterface $repository,
    ) {}

    public function __invoke(DeleteAuditSavedViewCommand $command): void
    {
        $view = $this->repository->findByUuid(
            Uuid::create($command->restaurantId),
            Uuid::create($command->uuid),
        ) ?? throw AuditSavedViewNotFoundException::withUuid($command->uuid);

        $this->repository->delete(
            Uuid::create($command->restaurantId),
            $view->uuid(),
        );
    }
}
