<?php

namespace App\Tax\Application\CreateTax;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Exception\TaxNameAlreadyExistsException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class CreateTax
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(CreateTaxCommand $command): CreateTaxResponse
    {
        if ($this->taxRepository->existsByName($command->name)) {
            throw TaxNameAlreadyExistsException::withName($command->name);
        }

        $tax = Tax::dddCreate(
            TaxName::create($command->name),
            TaxPercentage::create($command->percentage),
        );
        $this->taxRepository->save($tax);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('tax.created'),
            entityType: 'tax',
            entityId: $tax->id()->value(),
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'tax_name' => $tax->name()->value(),
                'percentage' => $tax->percentage()->value(),
            ],
        ));

        return CreateTaxResponse::create(
            id: $tax->id()->value(),
            name: $tax->name()->value(),
            percentage: $tax->percentage()->value(),
            createdAt: $tax->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $tax->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
