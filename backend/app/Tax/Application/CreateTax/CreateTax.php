<?php

namespace App\Tax\Application\CreateTax;

use App\Shared\Application\Event\EventBusInterface;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Exception\TaxNameAlreadyExistsException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class CreateTax
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
        private readonly EventBusInterface $eventBus,
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

        $this->eventBus->publish(...$tax->pullDomainEvents());

        return CreateTaxResponse::create(
            id: $tax->id()->value(),
            name: $tax->name()->value(),
            percentage: $tax->percentage()->value(),
            createdAt: $tax->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $tax->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
