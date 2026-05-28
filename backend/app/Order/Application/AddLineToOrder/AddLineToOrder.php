<?php

namespace App\Order\Application\AddLineToOrder;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Family\Domain\Exception\FamilyNotActiveException;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
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

final class AddLineToOrder
{
    public function __construct(
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TaxRepositoryInterface $taxRepository,
        private readonly FamilyRepositoryInterface $familyRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(AddLineToOrderCommand $command): AddLineToOrderResponse
    {
        $order = $this->orderRepository->findByUuid(Uuid::create($command->orderId));

        if ($order === null) {
            throw OrderNotFoundException::withId($command->orderId);
        }

        if (! $order->status()->isOpen()) {
            throw OrderIsNotOpenException::create();
        }

        $product = $this->productRepository->findById($command->productId);

        if ($product === null) {
            throw ProductNotFoundException::withId($command->productId);
        }

        if (! $product->isActive()) {
            throw ProductNotActiveException::withId($command->productId);
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

        if ($command->variantId !== null) {
            $variant = EloquentProductVariant::query()
                ->where('uuid', $command->variantId)
                ->whereHas('product', function ($q) use ($product): void {
                    $q->where('uuid', $product->id()->value());
                })
                ->first();

            if ($variant !== null) {
                $price = (int) $variant->price;
                $variantName = (string) $variant->name;
            }
        }

        // Añadir precio de modifiers al precio base
        $modifierTotal = 0;
        if ($command->modifiers !== null) {
            foreach ($command->modifiers as $modifier) {
                $modifierTotal += $modifier['price'] ?? 0;
            }
        }
        $price += $modifierTotal;

        $taxPercentage = $tax->percentage()->value();
        $quantity = OrderLineQuantity::create($command->quantity);

        $product->decreaseStock($quantity->value());
        $this->productRepository->save($product);

        $existing = $this->orderLineRepository->findMatchingMergeableLine(
            orderId: Uuid::create($command->orderId),
            productId: Uuid::create($command->productId),
            price: $price,
            taxPercentage: $taxPercentage,
        );

        if ($existing !== null) {
            $merged = $existing->withAddedQuantity($quantity->value());
            $this->orderLineRepository->save($merged);

            $this->recordLineAdded(
                command: $command,
                lineId: $merged->id()->value(),
                productName: $product->name()->value(),
                variantName: $variantName,
                quantity: $quantity->value(),
                unitPrice: $price,
                merged: true,
            );

            return AddLineToOrderResponse::create($merged);
        }

        $orderLine = OrderLine::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($command->restaurantId),
            orderId: Uuid::create($command->orderId),
            productId: Uuid::create($command->productId),
            variantId: $command->variantId !== null ? Uuid::create($command->variantId) : null,
            variantName: $variantName,
            modifiers: $command->modifiers,
            userId: Uuid::create($command->userId),
            quantity: $quantity,
            price: OrderLinePrice::create($price),
            taxPercentage: OrderLineTaxPercentage::create($taxPercentage),
            dinerNumber: $command->dinerNumber !== null ? OrderLineDinerNumber::create($command->dinerNumber) : null,
        );

        $this->orderLineRepository->save($orderLine);

        $this->recordLineAdded(
            command: $command,
            lineId: $orderLine->id()->value(),
            productName: $product->name()->value(),
            variantName: $variantName,
            quantity: $quantity->value(),
            unitPrice: $price,
            merged: false,
        );

        return AddLineToOrderResponse::create($orderLine);
    }

    private function recordLineAdded(
        AddLineToOrderCommand $command,
        string $lineId,
        string $productName,
        ?string $variantName,
        int $quantity,
        int $unitPrice,
        bool $merged,
    ): void {
        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('order.line_added'),
            entityType: 'order_line',
            entityId: $lineId,
            userId: Uuid::create($command->userId),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'order_id' => $command->orderId,
                'product_id' => $command->productId,
                'product_name' => $productName,
                'variant_name' => $variantName,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPrice,
                'merged' => $merged,
            ],
        ));
    }
}
