<?php

declare(strict_types=1);

namespace App\Sale\Application\AddLineToSale;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Event\SaleLineAdded;
use App\Sale\Domain\Exception\OrderLineNotFoundException;
use App\Sale\Domain\Exception\ProductNotActiveException;
use App\Sale\Domain\Interfaces\SaleLineRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Sale\Domain\ValueObject\SaleLinePrice;
use App\Sale\Domain\ValueObject\SaleLineQuantity;
use App\Sale\Domain\ValueObject\SaleLineTaxPercentage;
use App\Shared\Domain\ValueObject\Uuid;

final class AddLineToSale
{
    public function __construct(
        private readonly SaleLineRepositoryInterface $saleLineRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(AddLineToSaleCommand $command): AddLineToSaleResponse
    {
        $orderLine = $this->orderLineRepository->findByUuid(Uuid::create($command->orderLineId))
            ?? throw OrderLineNotFoundException::withId($command->orderLineId);

        if ($orderLine->isMenuLine() || $orderLine->productId() === null) {
            throw new \DomainException('Las líneas de menú aún no pueden cobrarse. Próximamente disponible.');
        }

        $productId = $orderLine->productId()->value();
        $product = $this->productRepository->findById($productId);

        if ($product === null) {
            throw OrderLineNotFoundException::withId($command->orderLineId);
        }

        if (! $product->isActive()) {
            throw ProductNotActiveException::create();
        }

        $saleLine = SaleLine::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($command->restaurantId),
            saleId: Uuid::create($command->saleId),
            orderLineId: Uuid::create($command->orderLineId),
            productId: Uuid::create($productId),
            userId: Uuid::create($command->userId),
            quantity: SaleLineQuantity::create($command->quantity),
            price: SaleLinePrice::create($command->price),
            taxPercentage: SaleLineTaxPercentage::create($command->taxPercentage),
        );

        $this->saleLineRepository->save($saleLine);

        $this->eventBus->publish(new SaleLineAdded(
            saleLineId: $saleLine->id()->value(),
            saleId: $command->saleId,
            orderLineId: $command->orderLineId,
            quantity: $command->quantity,
            priceCents: $command->price,
        ));

        return AddLineToSaleResponse::fromSaleLine($saleLine);
    }
}
