<?php

namespace App\ProductModifier\Application\DeleteProductModifier;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\ProductModifier\Domain\Exception\ProductModifierNotFoundException;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class DeleteProductModifier
{
    public function __construct(
        private ProductModifierRepositoryInterface $modifierRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(DeleteProductModifierCommand $command): void
    {
        $modifier = $this->modifierRepository->findById($command->id)
            ?? throw ProductModifierNotFoundException::withId($command->id);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('catalog.modifier_deleted'),
            entityType: 'product_modifier',
            entityId: $command->id,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            before: [
                'name' => $modifier->name()->value(),
                'type' => $modifier->type()->value(),
                'price' => $modifier->price()->value(),
                'active' => $modifier->isActive(),
            ],
            metadata: [
                'product_id' => $modifier->productId()->value(),
            ],
        ));

        $this->modifierRepository->deleteById($command->id);
    }
}
