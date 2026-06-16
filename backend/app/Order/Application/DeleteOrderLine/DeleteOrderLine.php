<?php

namespace App\Order\Application\DeleteOrderLine;

use App\Order\Domain\Event\OrderLineRemoved;
use App\Order\Domain\Exception\OrderIsNotOpenException;
use App\Order\Domain\Exception\OrderLineNotFoundException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class DeleteOrderLine
{
    public function __construct(
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(DeleteOrderLineCommand $command): void
    {
        $orderLineId = Uuid::create($command->lineId);
        $line = $this->orderLineRepository->findByUuid($orderLineId);

        if ($line === null) {
            throw OrderLineNotFoundException::withId($command->lineId);
        }

        $order = $this->orderRepository->findByUuid($line->orderId());

        if ($order === null) {
            throw OrderNotFoundException::withId($line->orderId()->value());
        }

        if (! $order->status()->isOpen()) {
            throw OrderIsNotOpenException::create();
        }

        $productName = null;
        if ($line->isMenuLine()) {
            $productName = $line->menuName();
            foreach ($line->menuSelections() ?? [] as $selection) {
                $product = $this->productRepository->findById($selection['product_id']);
                if ($product !== null) {
                    $product->increaseStock($line->quantity()->value());
                    $this->productRepository->save($product);
                }
            }
        } elseif ($line->productId() !== null) {
            $product = $this->productRepository->findById($line->productId()->value());
            if ($product !== null) {
                $productName = $product->name()->value();
                $product->increaseStock($line->quantity()->value());
                $this->productRepository->save($product);
            }
        }

        $this->orderLineRepository->delete($line->id());

        $this->eventBus->publish(new OrderLineRemoved(
            orderUuid: $line->orderId()->value(),
            productId: $line->productId()?->value() ?? $line->id()->value(),
            productName: $productName ?? '—',
            variantName: $line->variantName(),
            quantity: $line->quantity()->value(),
            unitPriceCents: $line->price()->value(),
            isMenuLine: $line->isMenuLine(),
            restaurantId: $order->restaurantId()->value(),
        ));
    }
}
