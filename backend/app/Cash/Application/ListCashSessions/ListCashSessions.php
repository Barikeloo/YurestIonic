<?php

declare(strict_types=1);

namespace App\Cash\Application\ListCashSessions;

use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class ListCashSessions
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly CashMovementRepositoryInterface $cashMovementRepository,
        private readonly SalePaymentRepositoryInterface $salePaymentRepository,
    ) {}

    public function __invoke(string $restaurantId): ListCashSessionsResponse
    {
        $restaurantUuid = Uuid::create($restaurantId);
        $sessions = $this->cashSessionRepository->findClosedByRestaurantId($restaurantUuid);

        $sessionsWithDetails = [];
        foreach ($sessions as $session) {
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
            $totalCashPayments = 0;
            $totalCardPayments = 0;
            $totalBizumPayments = 0;
            $totalOtherPayments = 0;
            $paymentsCount = 0;

            foreach ($payments as $payment) {
                $totalSales += $payment->amount()->toCents();
                $paymentsCount++;
                switch ($payment->method()->value()) {
                    case 'cash':
                        $totalCashPayments += $payment->amount()->toCents();
                        break;
                    case 'card':
                        $totalCardPayments += $payment->amount()->toCents();
                        break;
                    case 'bizum':
                        $totalBizumPayments += $payment->amount()->toCents();
                        break;
                    default:
                        $totalOtherPayments += $payment->amount()->toCents();
                        break;
                }
            }

            $sessionsWithDetails[] = [
                'session' => $session,
                'tickets' => $paymentsCount,
                'diners' => 0, // TODO: Calcular desde las órdenes
                'gross' => $totalSales,
                'discounts' => 0, // TODO: Calcular desde las órdenes
                'invitations' => 0, // TODO: Calcular desde las órdenes
                'invValue' => 0, // TODO: Calcular desde las órdenes
                'cancellations' => 0, // TODO: Calcular desde las órdenes
                'net' => $totalSales,
                'movIn' => $totalInMovements,
                'movOut' => $totalOutMovements,
            ];
        }

        return ListCashSessionsResponse::create($sessionsWithDetails);
    }
}
