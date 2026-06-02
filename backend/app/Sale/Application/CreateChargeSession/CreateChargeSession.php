<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateChargeSession;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Sale\Domain\Entity\ChargeSession;
use App\Sale\Domain\Exception\InvalidDinerCountException;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateChargeSession
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(CreateChargeSessionCommand $command): CreateChargeSessionResponse
    {
        $orderUuid = Uuid::create($command->orderId);
        $restaurantUuid = Uuid::create($command->restaurantId);

        $existingSession = $this->chargeSessionRepository->findActiveByOrderId($orderUuid);

        if ($existingSession !== null) {
            return $this->responseBuilder->build($existingSession);
        }

        $order = $this->orderRepository->findByUuid($orderUuid);

        if ($order === null) {
            throw new \DomainException('Order not found.');
        }

        $finalDinersCount = $command->dinersCount ?? $order->diners()->value() ?? 1;

        if ($finalDinersCount <= 0) {
            throw InvalidDinerCountException::create();
        }

        $orderLines = $this->orderLineRepository->findByOrderId($orderUuid);
        $totalCents = 0;
        foreach ($orderLines as $line) {
            $totalCents += $line->price()->value() * $line->quantity()->value();
        }

        if ($totalCents <= 0) {
            throw new \DomainException('Order has no items or total is zero.');
        }

        $chargeSession = ChargeSession::dddCreate(
            Uuid::generate(),
            $restaurantUuid,
            $orderUuid,
            Uuid::create($command->openedByUserId),
            $finalDinersCount,
            $totalCents,
        );

        $this->chargeSessionRepository->save($chargeSession);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $restaurantUuid,
            slug: ActionSlug::create('sale.charge_session_created'),
            entityType: 'charge_session',
            entityId: $chargeSession->id()->value(),
            userId: Uuid::create($command->openedByUserId),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'order_id' => $command->orderId,
                'diners_count' => $finalDinersCount,
                'total_cents' => $totalCents,
            ],
        ));

        return $this->responseBuilder->build($chargeSession);
    }
}
