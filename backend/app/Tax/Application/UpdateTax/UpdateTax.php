<?php

namespace App\Tax\Application\UpdateTax;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Exception\TaxNotFoundException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class UpdateTax
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(UpdateTaxCommand $command): UpdateTaxResponse
    {
        $tax = $this->taxRepository->findById($command->id);

        if ($tax === null) {
            throw TaxNotFoundException::withId($command->id);
        }

        $before = [
            'name' => $tax->name()->value(),
            'percentage' => $tax->percentage()->value(),
        ];

        $tax->update(
            $command->name !== null ? TaxName::create($command->name) : null,
            $command->percentage !== null ? TaxPercentage::create($command->percentage) : null,
        );
        $this->taxRepository->save($tax);

        $after = [
            'name' => $tax->name()->value(),
            'percentage' => $tax->percentage()->value(),
        ];

        $changedFields = [];
        if ($before['name'] !== $after['name']) {
            $changedFields[] = 'nombre';
        }
        if ($before['percentage'] !== $after['percentage']) {
            $changedFields[] = 'porcentaje';
        }

        if (count($changedFields) > 0) {
            $this->auditRecorder->record(new AuditEventDraft(
                restaurantId: Uuid::create($command->restaurantId),
                slug: ActionSlug::create('tax.updated'),
                entityType: 'tax',
                entityId: $command->id,
                userId: $command->userId !== null ? Uuid::create($command->userId) : null,
                deviceId: $command->deviceId,
                ipAddress: $command->ipAddress,
                before: $before,
                after: $after,
                metadata: [
                    'tax_name' => $after['name'],
                    'changed_fields' => implode(', ', $changedFields),
                ],
            ));
        }

        return UpdateTaxResponse::create(
            id: $tax->id()->value(),
            name: $tax->name()->value(),
            percentage: $tax->percentage()->value(),
            createdAt: $tax->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $tax->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
