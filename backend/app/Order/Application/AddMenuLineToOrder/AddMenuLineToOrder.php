<?php

declare(strict_types=1);

namespace App\Order\Application\AddMenuLineToOrder;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Menu\Domain\Entity\Menu;
use App\Menu\Domain\Entity\MenuSection;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Exception\OrderIsNotOpenException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderLineDinerNumber;
use App\Order\Domain\ValueObject\OrderLinePrice;
use App\Order\Domain\ValueObject\OrderLineQuantity;
use App\Order\Domain\ValueObject\OrderLineTaxPercentage;
use App\Product\Domain\Exception\ProductNotActiveException;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\ProductVariant\Infrastructure\Persistence\Models\EloquentProductVariant;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Exception\TaxNotFoundException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use DomainException;

final class AddMenuLineToOrder
{
    public function __construct(
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly MenuRepositoryInterface $menuRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly TaxRepositoryInterface $taxRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(AddMenuLineToOrderCommand $command): AddMenuLineToOrderResponse
    {
        $order = $this->orderRepository->findByUuid(Uuid::create($command->orderId));
        if ($order === null) {
            throw OrderNotFoundException::withId($command->orderId);
        }
        if (! $order->status()->isOpen()) {
            throw OrderIsNotOpenException::create();
        }

        $menu = $this->menuRepository->findById($command->menuId, includeArchived: false);
        if ($menu === null || $menu->isArchived() || ! $menu->isActive()) {
            throw MenuNotFoundException::withId($command->menuId);
        }

        $tax = $this->taxRepository->findById($menu->taxId()->value());
        if ($tax === null) {
            throw TaxNotFoundException::withId($menu->taxId()->value());
        }

        $sectionsById = [];
        foreach ($menu->sections() as $section) {
            $sectionsById[$section->id()->value()] = $section;
        }

        $this->validateChoiceRules($menu, $command->selections, $sectionsById);

        [$menuSelectionsJson, $extrasTotal] = $this->buildSelectionsJson($command->selections, $sectionsById);

        foreach ($menuSelectionsJson as $selection) {
            $product = $this->productRepository->findById($selection['product_id']);
            if ($product === null) {
                continue;

            }
            $product->decreaseStock(1);
            $this->productRepository->save($product);
        }

        $totalPrice = $menu->price()->value() + $extrasTotal;

        $orderLine = OrderLine::dddCreateMenuLine(
            id: Uuid::generate(),
            restaurantId: Uuid::create($command->restaurantId),
            orderId: Uuid::create($command->orderId),
            menuId: Uuid::create($command->menuId),
            menuName: $menu->name()->value(),
            menuSelections: $menuSelectionsJson,
            userId: Uuid::create($command->userId),
            quantity: OrderLineQuantity::create(1),
            price: OrderLinePrice::create($totalPrice),
            taxPercentage: OrderLineTaxPercentage::create($tax->percentage()->value()),
            dinerNumber: $command->dinerNumber !== null
                ? OrderLineDinerNumber::create($command->dinerNumber)
                : null,
            notes: $command->notes,
        );

        $this->orderLineRepository->save($orderLine);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('order.menu_line_added'),
            entityType: 'order_line',
            entityId: $orderLine->id()->value(),
            userId: Uuid::create($command->userId),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'order_id' => $command->orderId,
                'menu_id' => $command->menuId,
                'menu_name' => $menu->name()->value(),
                'quantity' => 1,
                'price_cents' => $totalPrice,
                'diner_number' => $command->dinerNumber,
            ],
        ));

        return AddMenuLineToOrderResponse::create($orderLine);
    }

    private function validateChoiceRules(Menu $menu, array $selections, array $sectionsById): void
    {
        $countBySection = [];
        foreach ($selections as $sel) {
            $sectionId = $sel['section_id'];
            if (! isset($sectionsById[$sectionId])) {
                throw new DomainException("La sección {$sectionId} no pertenece al menú {$menu->id()->value()}.");
            }
            $section = $sectionsById[$sectionId];

            $allowedProductIds = array_map(fn ($it) => $it->productId()->value(), $section->items());
            if (! in_array($sel['product_id'], $allowedProductIds, true)) {
                throw new DomainException(
                    "El producto {$sel['product_id']} no está disponible en la sección \"{$section->name()->value()}\"."
                );
            }

            $countBySection[$sectionId] = ($countBySection[$sectionId] ?? 0) + 1;
        }

        foreach ($menu->sections() as $section) {
            $count = $countBySection[$section->id()->value()] ?? 0;
            $rule = $section->choiceRule();
            if ($count < $rule->min() || $count > $rule->max()) {
                throw new DomainException(sprintf(
                    'La sección "%s" requiere entre %d y %d elecciones (recibidas: %d).',
                    $section->name()->value(),
                    $rule->min(),
                    $rule->max(),
                    $count,
                ));
            }
        }
    }

    private function buildSelectionsJson(array $selections, array $sectionsById): array
    {
        $extrasTotal = 0;
        $rows = [];

        foreach ($selections as $sel) {
            $section = $sectionsById[$sel['section_id']];

            $product = $this->productRepository->findById($sel['product_id']);
            if ($product === null) {
                throw ProductNotFoundException::withId($sel['product_id']);
            }
            if (! $product->isActive()) {
                throw ProductNotActiveException::withId($sel['product_id']);
            }

            $menuItemExtraPrice = 0;
            foreach ($section->items() as $item) {
                if ($item->productId()->value() === $sel['product_id']) {
                    $menuItemExtraPrice = $item->extraPrice()->value();
                    break;
                }
            }
            $extrasTotal += $menuItemExtraPrice;

            $variantName = null;
            if ($sel['variant_id'] !== null) {
                $variant = EloquentProductVariant::query()
                    ->where('uuid', $sel['variant_id'])
                    ->whereHas('product', function ($q) use ($product): void {
                        $q->where('uuid', $product->id()->value());
                    })
                    ->first();
                if ($variant !== null) {
                    $variantName = (string) $variant->name;
                }
            }

            foreach ($sel['modifiers'] ?? [] as $mod) {
                $extrasTotal += (int) ($mod['price'] ?? 0);
            }

            $rows[] = [
                'section_name' => $section->name()->value(),
                'product_id' => $sel['product_id'],
                'product_name' => $product->name()->value(),
                'variant_id' => $sel['variant_id'],
                'variant_name' => $variantName,
                'modifiers' => $sel['modifiers'] ?? [],
                'extra_price' => $menuItemExtraPrice,
            ];
        }

        return [$rows, $extrasTotal];
    }
}
