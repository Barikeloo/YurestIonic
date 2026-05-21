<?php

declare(strict_types=1);

namespace App\Order\Application\AddMenuLineToOrder;

/**
 * Añade un menú completo como línea a una orden.
 * El comensal personaliza cada sección eligiendo: producto, variante opcional
 * y modificadores opcionales (extras + acompañamientos).
 */
final readonly class AddMenuLineToOrderCommand
{
    /**
     * @param  array<int, array{section_id: string, product_id: string, variant_id: ?string, modifiers: array<int, array{id: string, name: string, price: int, type: string}>}>  $selections
     */
    public function __construct(
        public string $restaurantId,
        public string $orderId,
        public string $menuId,
        public string $userId,
        public ?int $dinerNumber,
        public array $selections,
        public ?string $notes = null,
    ) {}
}
