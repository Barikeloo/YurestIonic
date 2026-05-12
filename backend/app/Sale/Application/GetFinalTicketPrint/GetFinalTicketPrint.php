<?php

declare(strict_types=1);

namespace App\Sale\Application\GetFinalTicketPrint;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Sale\Domain\Exception\OrderFinalTicketNotFoundException;
use App\Sale\Domain\Interfaces\OrderFinalTicketRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

final class GetFinalTicketPrint
{
    public function __construct(
        private readonly OrderFinalTicketRepositoryInterface $orderFinalTicketRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly TableRepositoryInterface $tableRepository,
        private readonly RestaurantRepositoryInterface $restaurantRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(GetFinalTicketPrintCommand $command): GetFinalTicketPrintResponse
    {
        $orderUuid = Uuid::create($command->orderId);
        $ticket = $this->orderFinalTicketRepository->findByOrderId($orderUuid)
            ?? throw OrderFinalTicketNotFoundException::withOrderId($command->orderId);

        $order = $this->orderRepository->findByUuid($orderUuid);
        $table = $order !== null
            ? $this->tableRepository->findById($order->tableId()->value())
            : null;
        $restaurant = $this->restaurantRepository->findByUuid($ticket->restaurantId());

        $taxBreakdown = $this->buildTaxBreakdown($orderUuid->value());

        // Obtener hora
        $createdTime = $ticket->createdAt()->format('H:i');

        // Obtener líneas de la orden con nombres de productos
        $orderLines = $this->orderLineRepository->findByOrderId($orderUuid);
        $orderLinesPayload = [];
        $productNameCache = [];
        foreach ($orderLines as $line) {
            $productId = $line->productId()->value();
            if (! isset($productNameCache[$productId])) {
                $product = $this->productRepository->findById($productId);
                $productNameCache[$productId] = $product !== null ? $product->name()->value() : 'Producto';
            }
            $orderLinesPayload[] = [
                'name' => $productNameCache[$productId],
                'quantity' => $line->quantity()->value(),
                'price_cents' => $line->price()->value(),
                'total_cents' => $line->price()->value() * $line->quantity()->value(),
            ];
        }

        // Obtener operario (del ticket)
        $operator = null;
        $operatorUser = $this->userRepository->findById($ticket->closedByUserId()->value());
        if ($operatorUser !== null) {
            $operator = $operatorUser->name()->value();
        }

        // Obtener Z-report number (de la sesión de caja - por ahora null, se puede obtener de la venta)
        $zReportNumber = null;

        return GetFinalTicketPrintResponse::fromPayload(
            ticketId: $ticket->id()->value(),
            orderId: $ticket->orderId()->value(),
            ticketNumber: $ticket->ticketNumber(),
            restaurant: $restaurant !== null ? [
                'id' => $restaurant->uuid()->value(),
                'name' => $restaurant->name()->value(),
                'legal_name' => $restaurant->legalName()?->value(),
                'tax_id' => $restaurant->taxId()?->value(),
            ] : null,
            table: $table !== null ? [
                'id' => $table->id()->value(),
                'name' => $table->name()->value(),
            ] : null,
            totalConsumedCents: $ticket->totalConsumedCents(),
            totalPaidCents: $ticket->totalPaidCents(),
            taxBreakdown: $taxBreakdown,
            paymentsSnapshot: $ticket->paymentsSnapshot(),
            createdAt: $ticket->createdAt()->format(\DateTimeInterface::ATOM),
            createdTime: $createdTime,
            orderLines: $orderLinesPayload,
            zReportNumber: $zReportNumber,
            operator: $operator,
        );
    }

    private function buildTaxBreakdown(string $orderId): array
    {
        $lines = $this->orderLineRepository->findByOrderId(Uuid::create($orderId));
        $acc = [];

        foreach ($lines as $line) {
            $rate = $line->taxPercentage()->value();
            $gross = $line->price()->value() * $line->quantity()->value();
            $base = $rate > 0
                ? (int) round(($gross * 100) / (100 + $rate))
                : $gross;
            $tax = $gross - $base;

            if (! isset($acc[$rate])) {
                $acc[$rate] = [
                    'rate' => $rate,
                    'base_cents' => 0,
                    'tax_cents' => 0,
                    'gross_cents' => 0,
                ];
            }

            $acc[$rate]['base_cents'] += $base;
            $acc[$rate]['tax_cents'] += $tax;
            $acc[$rate]['gross_cents'] += $gross;
        }

        ksort($acc);

        return array_values($acc);
    }
}
