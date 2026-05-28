<?php

declare(strict_types=1);

namespace App\Menu\Application\SetMenuActive;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class SetMenuActive
{
    public function __construct(
        private MenuRepositoryInterface $menuRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(SetMenuActiveCommand $command): SetMenuActiveResponse
    {
        $menu = $this->menuRepository->findById($command->id, includeArchived: false)
            ?? throw MenuNotFoundException::withId($command->id);

        if ($command->active) {
            $menu->activate();
        } else {
            $menu->deactivate();
        }

        $this->menuRepository->save($menu);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create($command->active ? 'menu.activated' : 'menu.deactivated'),
            entityType: 'menu',
            entityId: $command->id,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'menu_name' => $menu->name()->value(),
            ],
        ));

        return SetMenuActiveResponse::fromEntity($menu);
    }
}
