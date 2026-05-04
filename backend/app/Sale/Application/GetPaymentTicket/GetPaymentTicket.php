<?php

declare(strict_types=1);

namespace App\Sale\Application\GetPaymentTicket;

use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

final class GetPaymentTicket
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly SalePaymentRepositoryInterface $salePaymentRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TableRepositoryInterface $tableRepository,
        private readonly RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(string $saleId): ?GetPaymentTicketResponse
    {
        $sale = $this->saleRepository->findByUuid(Uuid::create($saleId));

        if ($sale === null) {
            return null;
        }

        $payments = $this->salePaymentRepository->findBySaleId($sale->uuid());
        if (count($payments) === 0) {
            throw new \DomainException('No payments found for this sale');
        }

        $order = $this->orderRepository->findByUuid($sale->orderId());
        $table = $order !== null
            ? $this->tableRepository->findById($order->tableId()->value())
            : null;
        $restaurant = $this->restaurantRepository->findByUuid($sale->restaurantId());

        $totalPaidCents = 0;
        $latestSnapshot = null;
        $latestPaidAt = null;
        $paymentsPayload = [];

        foreach ($payments as $payment) {
            $amountCents = $payment->amount()->toCents();
            $totalPaidCents += $amountCents;

            $paidAt = $payment->createdAt()->value();
            if ($latestPaidAt === null || $paidAt > $latestPaidAt) {
                $latestPaidAt = $paidAt;
                $latestSnapshot = [
                    'total_cents' => $payment->snapshotTotalCents(),
                    'paid_cents' => $payment->snapshotPaidCents(),
                    'remaining_cents' => $payment->snapshotRemainingCents(),
                ];
            }

            $paymentsPayload[] = [
                'payment_id' => $payment->id()->value(),
                'method' => $payment->method()->value(),
                'amount_cents' => $amountCents,
                'diner_number' => $payment->dinerNumber(),
                'paid_at' => $paidAt->format(\DateTimeInterface::ATOM),
            ];
        }

        $totalConsumedCents = $latestSnapshot['total_cents'] ?? null;
        $remainingCents = $latestSnapshot['remaining_cents'] ?? null;
        $issuedAt = $latestPaidAt?->format(\DateTimeInterface::ATOM);

        return GetPaymentTicketResponse::fromPayload(
            $sale->uuid()->value(),
            $sale->orderId()->value(),
            $sale->ticketNumber()?->value(),
            $restaurant !== null ? [
                'id' => $restaurant->uuid()->value(),
                'name' => $restaurant->name()->value(),
                'legal_name' => $restaurant->legalName()?->value(),
                'tax_id' => $restaurant->taxId()?->value(),
            ] : null,
            $table !== null ? [
                'id' => $table->id()->value(),
                'name' => $table->name()->value(),
            ] : null,
            $totalConsumedCents,
            $totalPaidCents,
            $remainingCents,
            $paymentsPayload,
            $issuedAt,
            $latestSnapshot,
        );
    }
}
