<?php

namespace App\Order\Application\ListOrderLines;

use App\Order\Domain\Entity\OrderLine;

final class ListOrderLinesResponse
{
    /**
     * @param  array<int, array{id: string, name: string, price: int, type: string}>|null  $modifiers
     * @param  array<int, array{section_name: string, product_id: string, product_name: string, variant_id: ?string, variant_name: ?string, modifiers: array<int, array{id: string, name: string, price: int, type: string}>, extra_price: int}>|null  $menu_selections
     */
    public function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $restaurant_id,
        public readonly string $order_id,
        public readonly ?string $product_id,
        public readonly ?string $product_name,
        public readonly ?string $variant_id,
        public readonly ?string $variant_name,
        public readonly ?array $modifiers,
        public readonly ?string $menu_id,
        public readonly ?string $menu_name,
        public readonly ?array $menu_selections,
        public readonly string $user_id,
        public readonly int $quantity,
        public readonly int $price,
        public readonly int $tax_percentage,
        public readonly string $created_at,
        public readonly string $updated_at,
    ) {}

    public static function create(OrderLine $orderLine, ?string $productName = null): self
    {
        return new self(
            id: $orderLine->id()->value(),
            uuid: $orderLine->uuid()->value(),
            restaurant_id: $orderLine->restaurantId()->value(),
            order_id: $orderLine->orderId()->value(),
            product_id: $orderLine->productId()?->value(),
            product_name: $productName,
            variant_id: $orderLine->variantId()?->value(),
            variant_name: $orderLine->variantName(),
            modifiers: $orderLine->modifiers(),
            menu_id: $orderLine->menuId()?->value(),
            menu_name: $orderLine->menuName(),
            menu_selections: $orderLine->menuSelections(),
            user_id: $orderLine->userId()->value(),
            quantity: $orderLine->quantity()->value(),
            price: $orderLine->price()->value(),
            tax_percentage: $orderLine->taxPercentage()->value(),
            created_at: $orderLine->createdAt()->format('Y-m-d H:i:s'),
            updated_at: $orderLine->updatedAt()->format('Y-m-d H:i:s'),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'restaurant_id' => $this->restaurant_id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'variant_id' => $this->variant_id,
            'variant_name' => $this->variant_name,
            'modifiers' => $this->modifiers,
            'menu_id' => $this->menu_id,
            'menu_name' => $this->menu_name,
            'menu_selections' => $this->menu_selections,
            'user_id' => $this->user_id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'tax_percentage' => $this->tax_percentage,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
