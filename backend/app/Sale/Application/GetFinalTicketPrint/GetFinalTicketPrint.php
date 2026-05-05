<?php

declare(strict_types=1);

namespace App\Sale\Application\GetFinalTicketPrint;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Sale\Domain\Interfaces\OrderFinalTicketRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

final class GetFinalTicketPrint
{
    public function __construct(
        private readonly OrderFinalTicketRepositoryInterface $orderFinalTicketRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly TableRepositoryInterface $tableRepository,
        private readonly RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(string $orderId): ?GetFinalTicketPrintResponse
    {
        $orderUuid = Uuid::create($orderId);
        $ticket = $this->orderFinalTicketRepository->findByOrderId($orderUuid);

        if ($ticket === null) {
            return null;
        }

        $order = $this->orderRepository->findByUuid($orderUuid);
        $table = $order !== null
            ? $this->tableRepository->findById($order->tableId()->value())
            : null;
        $restaurant = $this->restaurantRepository->findByUuid($ticket->restaurantId());

        $taxBreakdown = $this->buildTaxBreakdown($orderUuid->value());

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
