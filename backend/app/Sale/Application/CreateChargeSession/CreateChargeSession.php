<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateChargeSession;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Sale\Domain\Entity\ChargeSession;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateChargeSession
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $orderId,
        string $openedByUserId,
        ?int $dinersCount = null,
    ): CreateChargeSessionResponse {
        $orderUuid = Uuid::create($orderId);
        $restaurantUuid = Uuid::create($restaurantId);

        $existingSession = $this->chargeSessionRepository->findActiveByOrderId($orderUuid);

        if ($existingSession !== null) {
            return $this->responseBuilder->build($existingSession);
        }

        $order = $this->orderRepository->findByUuid($orderUuid);

        if ($order === null) {
            throw new \DomainException('Order not found');
        }

        $finalDinersCount = $dinersCount ?? $order->diners()->value() ?? 1;

        if ($finalDinersCount <= 0) {
            throw new \DomainException('Diners count must be greater than 0');
        }

        $orderLines = $this->orderLineRepository->findByOrderId($orderUuid);
        $totalCents = 0;
        foreach ($orderLines as $line) {
            $totalCents += $line->price()->value() * $line->quantity()->value();
        }

        if ($totalCents <= 0) {
            throw new \DomainException('Order has no items or total is zero');
        }

        $chargeSession = ChargeSession::dddCreate(
            Uuid::generate(),
            $restaurantUuid,
            $orderUuid,
            Uuid::create($openedByUserId),
            $finalDinersCount,
            $totalCents,
        );

        $this->chargeSessionRepository->save($chargeSession);

        return $this->responseBuilder->build($chargeSession);
    }
}
