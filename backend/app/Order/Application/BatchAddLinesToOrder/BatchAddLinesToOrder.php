<?php

declare(strict_types=1);

namespace App\Order\Application\BatchAddLinesToOrder;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Family\Domain\Exception\FamilyNotActiveException;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
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

final class BatchAddLinesToOrder
{
    public function __construct(
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TaxRepositoryInterface $taxRepository,
        private readonly FamilyRepositoryInterface $familyRepository,
        private readonly MenuRepositoryInterface $menuRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(BatchAddLinesToOrderCommand $command): BatchAddLinesToOrderResponse
    {
        $order = $this->orderRepository->findByUuid(Uuid::create($command->orderId));

        if ($order === null) {
            throw OrderNotFoundException::withId($command->orderId);
        }

        if (! $order->status()->isOpen()) {
            throw OrderIsNotOpenException::create();
        }

        $auditItems = [];
        $productResults = [];
        $menuResults = [];

        // ── Product lines ──────────────────────────────────────
        foreach ($command->productLines as $line) {
            $result = $this->processProductLine($command, $line);
            $productResults[] = $result;

            $auditItems[] = [
                'type' => 'product',
                'name' => $result['product_name'],
                'quantity' => $result['quantity'],
                'unit_price_cents' => $result['price'],
                'merged' => $result['merged'],
            ];
        }

        // ── Menu lines ─────────────────────────────────────────
        foreach ($command->menuLines as $line) {
            $result = $this->processMenuLine($command, $line);
            $menuResults[] = $result;

            $auditItems[] = [
                'type' => 'menu',
                'name' => $result['menu_name'],
                'quantity' => $result['quantity'],
                'unit_price_cents' => $result['price'],
                'merged' => false,
            ];
        }

        // ── Single batch audit event ───────────────────────────
        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('order.comanda_sent'),
            entityType: 'order',
            entityId: $command->orderId,
            userId: Uuid::create($command->userId),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'order_id' => $command->orderId,
                'items' => $auditItems,
                'total_lines' => count($auditItems),
                'items_summary' => implode(', ', array_map(
                    static fn (array $item): string => $item['name'],
                    $auditItems,
                )),
            ],
        ));

        return new BatchAddLinesToOrderResponse(
            productLines: $productResults,
            menuLines: $menuResults,
        );
    }

    /**
     * @param array{product_id: string, quantity: int, variant_id: ?string, modifiers: ?list<array{id: string, name: string, price: int, type: string}>, diner_number: ?int} $line
     * @return array{id: string, product_name: string, quantity: int, price: int, tax_percentage: int, merged: bool}
     */
    private function processProductLine(BatchAddLinesToOrderCommand $command, array $line): array
    {
        $product = $this->productRepository->findById($line['product_id']);

        if ($product === null) {
            throw ProductNotFoundException::withId($line['product_id']);
        }

        if (! $product->isActive()) {
            throw ProductNotActiveException::withId($line['product_id']);
        }

        $family = $this->familyRepository->findById($product->familyId()->value());

        if ($family === null) {
            throw FamilyNotFoundException::withId($product->familyId()->value());
        }

        if (! $family->isActive()) {
            throw FamilyNotActiveException::withId($product->familyId()->value());
        }

        $tax = $this->taxRepository->findById($product->taxId()->value());

        if ($tax === null) {
            throw TaxNotFoundException::withId($product->taxId()->value());
        }

        $price = $product->price()->value();
        $variantName = null;

        if (($line['variant_id'] ?? null) !== null) {
            $variant = EloquentProductVariant::query()
                ->where('uuid', $line['variant_id'])
                ->whereHas('product', function ($q) use ($product): void {
                    $q->where('uuid', $product->id()->value());
                })
                ->first();

            if ($variant !== null) {
                $price = (int) $variant->price;
                $variantName = (string) $variant->name;
            }
        }

        $modifierTotal = 0;
        $modifiers = $line['modifiers'] ?? null;
        if ($modifiers !== null) {
            foreach ($modifiers as $modifier) {
                $modifierTotal += $modifier['price'] ?? 0;
            }
        }
        $price += $modifierTotal;

        $taxPercentage = $tax->percentage()->value();
        $quantity = OrderLineQuantity::create($line['quantity']);

        $product->decreaseStock($quantity->value());
        $this->productRepository->save($product);

        $existing = $this->orderLineRepository->findMatchingMergeableLine(
            orderId: Uuid::create($command->orderId),
            productId: Uuid::create($line['product_id']),
            price: $price,
            taxPercentage: $taxPercentage,
        );

        if ($existing !== null) {
            $merged = $existing->withAddedQuantity($quantity->value());
            $this->orderLineRepository->save($merged);

            return [
                'id' => $merged->id()->value(),
                'product_name' => $product->name()->value(),
                'quantity' => $merged->quantity()->value(),
                'price' => $price,
                'tax_percentage' => $taxPercentage,
                'merged' => true,
            ];
        }

        $orderLine = OrderLine::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($command->restaurantId),
            orderId: Uuid::create($command->orderId),
            productId: Uuid::create($line['product_id']),
            variantId: ($line['variant_id'] ?? null) !== null ? Uuid::create($line['variant_id']) : null,
            variantName: $variantName,
            modifiers: $modifiers,
            userId: Uuid::create($command->userId),
            quantity: $quantity,
            price: OrderLinePrice::create($price),
            taxPercentage: OrderLineTaxPercentage::create($taxPercentage),
            dinerNumber: ($line['diner_number'] ?? null) !== null ? OrderLineDinerNumber::create($line['diner_number']) : null,
        );

        $this->orderLineRepository->save($orderLine);

        return [
            'id' => $orderLine->id()->value(),
            'product_name' => $product->name()->value(),
            'quantity' => $quantity->value(),
            'price' => $price,
            'tax_percentage' => $taxPercentage,
            'merged' => false,
        ];
    }

    /**
     * @param array{menu_id: string, notes: ?string, selections: list<array{section_id: string, product_id: string, variant_id: ?string, modifiers: list<array{id: string, name: string, price: int, type: string}>}>} $line
     * @return array{id: string, menu_name: string, quantity: int, price: int, tax_percentage: int}
     */
    private function processMenuLine(BatchAddLinesToOrderCommand $command, array $line): array
    {
        $menu = $this->menuRepository->findById($line['menu_id'], includeArchived: false);

        if ($menu === null || $menu->isArchived() || ! $menu->isActive()) {
            throw MenuNotFoundException::withId($line['menu_id']);
        }

        $tax = $this->taxRepository->findById($menu->taxId()->value());

        if ($tax === null) {
            throw TaxNotFoundException::withId($menu->taxId()->value());
        }

        $sectionsById = [];
        foreach ($menu->sections() as $section) {
            $sectionsById[$section->id()->value()] = $section;
        }

        $this->validateChoiceRules($menu, $line['selections'], $sectionsById);

        [$menuSelectionsJson, $extrasTotal] = $this->buildSelectionsJson($line['selections'], $sectionsById);

        foreach ($menuSelectionsJson as $selection) {
            $product = $this->productRepository->findById($selection['product_id']);
            if ($product !== null) {
                $product->decreaseStock(1);
                $this->productRepository->save($product);
            }
        }

        $totalPrice = $menu->price()->value() + $extrasTotal;

        $orderLine = OrderLine::dddCreateMenuLine(
            id: Uuid::generate(),
            restaurantId: Uuid::create($command->restaurantId),
            orderId: Uuid::create($command->orderId),
            menuId: Uuid::create($line['menu_id']),
            menuName: $menu->name()->value(),
            menuSelections: $menuSelectionsJson,
            userId: Uuid::create($command->userId),
            quantity: OrderLineQuantity::create(1),
            price: OrderLinePrice::create($totalPrice),
            taxPercentage: OrderLineTaxPercentage::create($tax->percentage()->value()),
            notes: $line['notes'] ?? null,
        );

        $this->orderLineRepository->save($orderLine);

        return [
            'id' => $orderLine->id()->value(),
            'menu_name' => $menu->name()->value(),
            'quantity' => 1,
            'price' => $totalPrice,
            'tax_percentage' => $tax->percentage()->value(),
        ];
    }

    /**
     * @param array<int, array{section_id: string, product_id: string, variant_id: ?string, modifiers: array<int, array{id: string, name: string, price: int, type: string}>}> $selections
     * @param array<string, \App\Menu\Domain\Entity\MenuSection> $sectionsById
     */
    private function validateChoiceRules(\App\Menu\Domain\Entity\Menu $menu, array $selections, array $sectionsById): void
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
                    "El producto {$sel['product_id']} no está disponible en la sección \"{$section->name()->value()}.\""
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

    /**
     * @param array<int, array{section_id: string, product_id: string, variant_id: ?string, modifiers: array<int, array{id: string, name: string, price: int, type: string}>}> $selections
     * @param array<string, \App\Menu\Domain\Entity\MenuSection> $sectionsById
     * @return array{0: array<int, array{section_name: string, product_id: string, product_name: string, variant_id: ?string, variant_name: ?string, modifiers: array<int, array{id: string, name: string, price: int, type: string}>, extra_price: int}>, 1: int}
     */
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
