<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetGuestCart;

use App\GuestOrder\Domain\ReadModel\CartLineData;

final readonly class GetGuestCartResponse
{
    private function __construct(
        public array $lines,
        public int $totalCents,
    ) {}

    public static function create(array $lines): self
    {
        $total = array_reduce($lines, fn (int $carry, CartLineData $l): int => $carry + ($l->unitPrice * $l->quantity), 0);

        return new self(lines: $lines, totalCents: $total);
    }

    public function toArray(): array
    {
        return [
            'lines'       => array_map(fn (CartLineData $l): array => $this->lineToArray($l), $this->lines),
            'total_cents' => $this->totalCents,
        ];
    }

    private function lineToArray(CartLineData $l): array
    {
        return [
            'id'           => $l->id,
            'product_id'   => $l->productId,
            'product_name' => $l->productName,
            'menu_id'      => $l->menuId,
            'menu_name'    => $l->menuName,
            'variant_id'   => $l->variantId,
            'variant_name' => $l->variantName,
            'modifiers'    => $l->modifiers,
            'quantity'     => $l->quantity,
            'unit_price'   => $l->unitPrice,
            'notes'        => $l->notes,
            'send_status'  => $l->sendStatus,
        ];
    }
}
