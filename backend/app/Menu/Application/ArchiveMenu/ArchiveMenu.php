<?php

declare(strict_types=1);

namespace App\Menu\Application\ArchiveMenu;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class ArchiveMenu
{
    public function __construct(
        private MenuRepositoryInterface $menuRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(ArchiveMenuCommand $command): void
    {
        $menu = $this->menuRepository->findById($command->id, includeArchived: true)
            ?? throw MenuNotFoundException::withId($command->id);

        if ($menu->isArchived()) {
            return;
        }

        $menuName = $menu->name()->value();

        $menu->archive();
        $this->menuRepository->save($menu);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('menu.archived'),
            entityType: 'menu',
            entityId: $command->id,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'menu_name' => $menuName,
            ],
        ));
    }
}
