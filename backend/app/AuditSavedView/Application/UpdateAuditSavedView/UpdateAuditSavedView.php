<?php

declare(strict_types=1);

namespace App\AuditSavedView\Application\UpdateAuditSavedView;

use App\AuditSavedView\Domain\Exception\AuditSavedViewNotFoundException;
use App\AuditSavedView\Domain\Interfaces\AuditSavedViewRepositoryInterface;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateAuditSavedView
{
    public function __construct(
        private readonly AuditSavedViewRepositoryInterface $repository,
    ) {}

    public function __invoke(UpdateAuditSavedViewCommand $command): UpdateAuditSavedViewResponse
    {
        $view = $this->repository->findByUuid(
            Uuid::create($command->restaurantId),
            Uuid::create($command->uuid),
        ) ?? throw AuditSavedViewNotFoundException::withUuid($command->uuid);

        $updated = $view;
        $now = DomainDateTime::now();

        if ($command->name !== null) {
            $updated = $updated->withUpdatedName($command->name, $now);
        }

        if ($command->filters !== null) {
            $updated = $updated->withUpdatedFilters($command->filters, $now);
        }

        if ($command->icon !== null) {
            // Rebuild with new icon. Since there's no dedicated withUpdatedIcon,
            // we rebuild from current state.
            $updated = \App\AuditSavedView\Domain\Entity\AuditSavedView::dddCreate(
                uuid: $updated->uuid(),
                restaurantId: $updated->restaurantId(),
                userId: $updated->userId(),
                name: $updated->name(),
                icon: $command->icon,
                filters: $updated->filters(),
                createdAt: $updated->createdAt(),
                updatedAt: $now,
            );
        }

        $this->repository->save($updated);

        return UpdateAuditSavedViewResponse::create(
            uuid: $updated->uuid()->value(),
            name: $updated->name(),
            icon: $updated->icon(),
            filters: $updated->filters(),
            updatedAt: $updated->updatedAt()->format('Y-m-d H:i:s'),
        );
    }
}
