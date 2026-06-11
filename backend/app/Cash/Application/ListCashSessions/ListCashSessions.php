<?php

declare(strict_types=1);

namespace App\Cash\Application\ListCashSessions;

use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserRepositoryInterface;

final class ListCashSessions
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly CashMovementRepositoryInterface $cashMovementRepository,
        private readonly SalePaymentRepositoryInterface $salePaymentRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(ListCashSessionsCommand $command): ListCashSessionsResponse
    {
        $sessions = $this->cashSessionRepository->findByRestaurantId(
            Uuid::create($command->restaurantId),
        );

        usort($sessions, fn (CashSession $a, CashSession $b) =>
            ($b->closedAt()?->value() ?? $b->openedAt()?->value())
            <=>
            ($a->closedAt()?->value() ?? $a->openedAt()?->value())
        );

        $items = array_map(
            fn (CashSession $session): ListCashSessionsItemResponse => $this->mapSession($session),
            $sessions,
        );

        return ListCashSessionsResponse::create($items);
    }

    private function mapSession(CashSession $session): ListCashSessionsItemResponse
    {
        $sessionUuid = $session->uuid();
        $movements = $this->cashMovementRepository->findByCashSessionId($sessionUuid);
        $payments = $this->salePaymentRepository->findByCashSessionId($sessionUuid);

        $totalInMovements = 0;
        $totalOutMovements = 0;
        foreach ($movements as $movement) {
            if ($movement->type()->isIn()) {
                $totalInMovements += $movement->amount()->toCents();
            } else {
                $totalOutMovements += $movement->amount()->toCents();
            }
        }

        $totalSales = 0;
        $paymentsCount = 0;
        foreach ($payments as $payment) {
            $totalSales += $payment->amount()->toCents();
            $paymentsCount++;
        }

        $operator = $this->userRepository->findById($session->openedByUserId()->value());

        return ListCashSessionsItemResponse::create(
            uuid: $session->uuid()->value(),
            deviceId: $session->deviceId()->value(),
            openedByUserId: $session->openedByUserId()->value(),
            closedByUserId: $session->closedByUserId()?->value(),
            openedAt: $session->openedAt()?->value()->format('Y-m-d\TH:i:s'),
            closedAt: $session->closedAt()?->value()->format('Y-m-d\TH:i:s'),
            initialAmountCents: $session->initialAmount()->toCents(),
            finalAmountCents: $session->finalAmount()?->toCents(),
            expectedAmountCents: $session->expectedAmount()?->toCents(),
            discrepancyCents: $session->discrepancy()?->toCents(),
            discrepancyReason: $session->discrepancyReason(),
            zReportNumber: $session->zReportNumber()?->value(),
            status: $session->status()->value(),
            tickets: $paymentsCount,
            diners: 0,
            gross: $totalSales,
            discounts: 0,
            invitations: 0,
            invValue: 0,
            cancellations: 0,
            net: $totalSales,
            movIn: $totalInMovements,
            movOut: $totalOutMovements,
            operatorName: $operator?->name()->value(),
        );
    }
}
