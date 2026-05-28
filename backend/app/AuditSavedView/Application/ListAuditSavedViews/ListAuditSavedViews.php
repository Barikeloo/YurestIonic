<?php

declare(strict_types=1);

namespace App\AuditSavedView\Application\ListAuditSavedViews;

use App\AuditSavedView\Domain\Entity\AuditSavedView;
use App\AuditSavedView\Domain\Interfaces\AuditSavedViewRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class ListAuditSavedViews
{
    public function __construct(
        private readonly AuditSavedViewRepositoryInterface $repository,
    ) {}

    public function __invoke(ListAuditSavedViewsCommand $command): ListAuditSavedViewsResponse
    {
        $views = $this->repository->listByRestaurantAndUser(
            Uuid::create($command->restaurantId),
            Uuid::create($command->userId),
        );

        $items = array_map(
            static fn (AuditSavedView $v): AuditSavedViewItemResponse => AuditSavedViewItemResponse::create(
                uuid: $v->uuid()->value(),
                name: $v->name(),
                icon: $v->icon(),
                filters: $v->filters(),
                createdAt: $v->createdAt()->format('Y-m-d H:i:s'),
                updatedAt: $v->updatedAt()->format('Y-m-d H:i:s'),
            ),
            $views,
        );

        return ListAuditSavedViewsResponse::create(items: $items);
    }
}
