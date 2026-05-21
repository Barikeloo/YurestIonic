<?php

declare(strict_types=1);

namespace App\Order\Application\AddMenuLineToOrder;

use App\Order\Domain\Entity\OrderLine;

final class AddMenuLineToOrderResponse
{
    /**
     * @param  array<int, array{section_name: string, product_id: string, product_name: string, variant_id: ?string, variant_name: ?string, modifiers: array<int, array{id: string, name: string, price: int, type: string}>, extra_price: int}>  $menuSelections
     */
    private function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $orderId,
        public readonly string $menuId,
        public readonly string $menuName,
        public readonly array $menuSelections,
        public readonly int $quantity,
        public readonly int $price,
        public readonly int $taxPercentage,
    ) {}

    public static function create(OrderLine $orderLine): self
    {
        return new self(
            id: $orderLine->id()->value(),
            uuid: $orderLine->uuid()->value(),
            orderId: $orderLine->orderId()->value(),
            menuId: $orderLine->menuId()?->value() ?? '',
            menuName: $orderLine->menuName() ?? '',
            menuSelections: $orderLine->menuSelections() ?? [],
            quantity: $orderLine->quantity()->value(),
            price: $orderLine->price()->value(),
            taxPercentage: $orderLine->taxPercentage()->value(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'order_id' => $this->orderId,
            'menu_id' => $this->menuId,
            'menu_name' => $this->menuName,
            'menu_selections' => $this->menuSelections,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'tax_percentage' => $this->taxPercentage,
        ];
    }
}
