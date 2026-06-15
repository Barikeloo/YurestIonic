<?php

declare(strict_types=1);

namespace App\Sale\Application\AssignChargeSessionLines;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Sale\Application\CreateChargeSession\ChargeSessionResponseBuilder;
use App\Sale\Application\CreateChargeSession\CreateChargeSessionResponse;
use App\Sale\Domain\Entity\ChargeSessionLineAssignment;
use App\Sale\Domain\Event\ChargeSessionLinesAssigned;
use App\Sale\Domain\Exception\ChargeSessionNotActiveException;
use App\Sale\Domain\Exception\ChargeSessionNotFoundException;
use App\Sale\Domain\Exception\InvalidDinerCountException;
use App\Sale\Domain\Interfaces\ChargeSessionLineAssignmentRepositoryInterface;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class AssignChargeSessionLines
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly ChargeSessionLineAssignmentRepositoryInterface $assignmentRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(AssignChargeSessionLinesCommand $command): CreateChargeSessionResponse
    {
        $sessionUuid = Uuid::create($command->chargeSessionId);

        $session = $this->chargeSessionRepository->findById($sessionUuid)
            ?? throw ChargeSessionNotFoundException::withId($command->chargeSessionId);

        if (! $session->status()->isActive()) {
            throw ChargeSessionNotActiveException::create();
        }

        $orderLines = $this->orderLineRepository->findByOrderId($session->orderId());
        $validLineIds = [];
        foreach ($orderLines as $line) {
            $validLineIds[$line->uuid()->value()] = true;
        }

        $entities = [];
        $seenLineIds = [];
        foreach ($command->assignments as $raw) {
            $lineId = (string) ($raw['order_line_id'] ?? '');
            $dinerNumber = (int) ($raw['diner_number'] ?? 0);

            if (! isset($validLineIds[$lineId])) {
                throw new \DomainException("Order line {$lineId} does not belong to this session's order.");
            }

            if ($dinerNumber < 1 || $dinerNumber > $session->dinersCount()) {
                throw InvalidDinerCountException::invalidDinerNumber();
            }

            if (isset($seenLineIds[$lineId])) {
                throw new \DomainException("Order line {$lineId} cannot be assigned twice in the same request.");
            }
            $seenLineIds[$lineId] = true;

            $entities[] = ChargeSessionLineAssignment::dddCreate(
                id: Uuid::generate(),
                chargeSessionId: $sessionUuid,
                orderLineId: Uuid::create($lineId),
                dinerNumber: $dinerNumber,
            );
        }

        $this->assignmentRepository->replaceForSession($sessionUuid, $entities);

        $dinerCounts = array_count_values(array_map(
            static fn (array $a): int => $a['diner_number'],
            $command->assignments,
        ));
        $summaryParts = [];
        foreach ($dinerCounts as $diner => $count) {
            $summaryParts[] = "{$diner}:{$count}";
        }

        $this->eventBus->publish(new ChargeSessionLinesAssigned(
            chargeSessionId: $session->id()->value(),
            assignmentsSummary: implode(', ', $summaryParts),
            totalAssigned: count($command->assignments),
        ));

        return $this->responseBuilder->build($session);
    }
}
