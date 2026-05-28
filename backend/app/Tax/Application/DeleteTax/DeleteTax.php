<?php

namespace App\Tax\Application\DeleteTax;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Exception\TaxNotFoundException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;

class DeleteTax
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(DeleteTaxCommand $command): void
    {
        $tax = $this->taxRepository->findById($command->id)
            ?? throw TaxNotFoundException::withId($command->id);

        $taxName = $tax->name()->value();
        $taxPercentage = $tax->percentage()->value();

        $this->taxRepository->deleteById($command->id);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('tax.deleted'),
            entityType: 'tax',
            entityId: $command->id,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'tax_name' => $taxName,
                'percentage' => $taxPercentage,
            ],
        ));
    }
}
