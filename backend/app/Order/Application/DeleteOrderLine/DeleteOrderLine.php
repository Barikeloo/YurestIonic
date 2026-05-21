<?php

namespace App\Order\Application\DeleteOrderLine;

use App\Order\Domain\Exception\OrderIsNotOpenException;
use App\Order\Domain\Exception\OrderLineNotFoundException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class DeleteOrderLine
{
    public function __construct(
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProductRepositoryInterface $productRepository,
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

        // En líneas de menú, devolvemos stock a cada producto elegido (vienen en menu_selections);
        // en líneas de producto, solo a su productId.
        if ($line->isMenuLine()) {
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
                $product->increaseStock($line->quantity()->value());
                $this->productRepository->save($product);
            }
        }

        $this->orderLineRepository->delete($line->id());
    }
}
