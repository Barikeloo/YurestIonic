<?php

declare(strict_types=1);

namespace App\AuditSavedView\Application\CreateAuditSavedView;

use App\AuditSavedView\Domain\Entity\AuditSavedView;
use App\AuditSavedView\Domain\Interfaces\AuditSavedViewRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateAuditSavedView
{
    public function __construct(
        private readonly AuditSavedViewRepositoryInterface $repository,
    ) {}

    public function __invoke(CreateAuditSavedViewCommand $command): CreateAuditSavedViewResponse
    {
        $view = AuditSavedView::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: Uuid::create($command->restaurantId),
            userId: Uuid::create($command->userId),
            name: $command->name,
            icon: $command->icon,
            filters: $command->filters,
        );

        $this->repository->save($view);

        return CreateAuditSavedViewResponse::create(
            uuid: $view->uuid()->value(),
            name: $view->name(),
            icon: $view->icon(),
            filters: $view->filters(),
            createdAt: $view->createdAt()->format('Y-m-d H:i:s'),
            updatedAt: $view->updatedAt()->format('Y-m-d H:i:s'),
        );
    }
}
