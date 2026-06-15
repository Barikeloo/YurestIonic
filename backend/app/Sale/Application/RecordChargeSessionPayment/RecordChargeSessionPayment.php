<?php

declare(strict_types=1);

namespace App\Sale\Application\RecordChargeSessionPayment;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Sale\Application\CreateChargeSession\ChargeSessionResponseBuilder;
use App\Sale\Application\CreateOrderFinalTicket\CreateOrderFinalTicket;
use App\Sale\Application\CreateSale\CreateSale;
use App\Sale\Domain\Event\ChargeSessionPaymentRecorded;
use App\Sale\Domain\Exception\ChargeSessionHasNoRemainingDebtException;
use App\Sale\Domain\Exception\ChargeSessionNotActiveException;
use App\Sale\Domain\Exception\ChargeSessionNotFoundException;
use App\Sale\Domain\Exception\InvalidDinerCountException;
use App\Sale\Domain\Exception\PaymentAmountExceedsDebtException;
use App\Sale\Domain\Exception\PaymentAmountMustBePositiveException;
use App\Sale\Domain\Interfaces\ChargeSessionLineAssignmentRepositoryInterface;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class RecordChargeSessionPayment
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
        private readonly CreateSale $createSale,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CreateOrderFinalTicket $createOrderFinalTicket,
        private readonly ChargeSessionLineAssignmentRepositoryInterface $assignmentRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(RecordChargeSessionPaymentCommand $command): RecordChargeSessionPaymentResponse
    {
        $sessionUuid = Uuid::create($command->chargeSessionId);

        $session = $this->chargeSessionRepository->findById($sessionUuid)
            ?? throw ChargeSessionNotFoundException::withId($command->chargeSessionId);

        if (! $session->status()->isActive()) {
            throw ChargeSessionNotActiveException::create();
        }

        if ($command->dinerNumber !== null && ($command->dinerNumber < 1 || $command->dinerNumber > $session->dinersCount())) {
            throw InvalidDinerCountException::invalidDinerNumber();
        }

        [$totalCents, $paidCents, $paidDinerNumbers] = $this->responseBuilder->collect($session);

        $remainingCents = max(0, $totalCents - $paidCents);

        if ($remainingCents <= 0) {
            throw ChargeSessionHasNoRemainingDebtException::create();
        }

        $amountCents = $command->amountCents;
        if ($amountCents === null) {
            $unpaidDinersCount = max(1, $session->dinersCount() - count($paidDinerNumbers));
            $amountCents = $unpaidDinersCount === 1
                ? $remainingCents
                : (int) floor($remainingCents / $unpaidDinersCount);
        }

        if ($amountCents <= 0) {
            throw PaymentAmountMustBePositiveException::create();
        }

        if ($amountCents > $remainingCents) {
            throw PaymentAmountExceedsDebtException::create($amountCents, $remainingCents);
        }

        $newPaidCents = $paidCents + $amountCents;
        $newRemainingCents = max(0, $totalCents - $newPaidCents);
        $isSessionComplete = $newRemainingCents === 0;

        $payment = [
            'method' => $command->paymentMethod,
            'amount_cents' => $amountCents,
            'snapshot_total_cents' => $totalCents,
            'snapshot_paid_cents' => $newPaidCents,
            'snapshot_remaining_cents' => $newRemainingCents,
        ];
        if ($command->dinerNumber !== null) {
            $payment['diner_number'] = $command->dinerNumber;
        }

        $orderLineIds = [];
        if ($command->dinerNumber !== null) {
            $paidLineIds = array_flip($this->responseBuilder->collectPaidOrderLineIds($session));
            $assignments = $this->assignmentRepository->findBySessionId($session->id());
            foreach ($assignments as $assignment) {
                if ($assignment->dinerNumber() === $command->dinerNumber) {
                    $lineId = $assignment->orderLineId()->value();

                    if (! isset($paidLineIds[$lineId])) {
                        $orderLineIds[] = $lineId;
                    }
                }
            }
        }

        $saleResponse = ($this->createSale)(
            restaurantId: $session->restaurantId()->value(),
            orderId: $session->orderId()->value(),
            openedByUserId: $command->openedByUserId,
            closedByUserId: $command->closedByUserId,
            deviceId: $command->deviceId,
            payments: [$payment],
            orderLineIds: $orderLineIds,
            chargeSessionId: $command->chargeSessionId,
        );

        $newPaidDinersCount = $command->dinerNumber !== null && ! in_array($command->dinerNumber, $paidDinerNumbers, true)
            ? count($paidDinerNumbers) + 1
            : count($paidDinerNumbers);

        if ($isSessionComplete) {
            $session->markCompleted();
            $this->chargeSessionRepository->save($session);

            $order = $this->orderRepository->findByUuid($session->orderId());
            if ($order !== null) {
                $order->close(Uuid::create($command->closedByUserId));
                $this->orderRepository->save($order);
            }

            ($this->createOrderFinalTicket)(
                orderId: $session->orderId()->value(),
                closedByUserId: $command->closedByUserId,
            );
        }

        $this->eventBus->publish(new ChargeSessionPaymentRecorded(
            chargeSessionId: $session->id()->value(),
            paymentMethod: $command->paymentMethod,
            amountFormatted: number_format($amountCents / 100, 2).' €',
            dinerNumber: $command->dinerNumber,
            isSessionComplete: $isSessionComplete,
        ));

        return RecordChargeSessionPaymentResponse::create(
            paymentId: $saleResponse->id,
            chargeSessionId: $command->chargeSessionId,
            dinerNumber: $command->dinerNumber,
            amountCents: $amountCents,
            paymentMethod: $command->paymentMethod,
            status: 'completed',
            sessionPaidDinersCount: $newPaidDinersCount,
            sessionStatus: $isSessionComplete ? 'completed' : 'active',
            sessionRemainingCents: $newRemainingCents,
            isSessionComplete: $isSessionComplete,
        );
    }
}
