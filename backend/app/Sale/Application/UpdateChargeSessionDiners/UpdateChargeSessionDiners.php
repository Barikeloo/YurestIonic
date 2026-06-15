<?php

declare(strict_types=1);

namespace App\Sale\Application\UpdateChargeSessionDiners;

use App\Sale\Application\CreateChargeSession\ChargeSessionResponseBuilder;
use App\Sale\Domain\Event\ChargeSessionDinersUpdated;
use App\Sale\Domain\Exception\ChargeSessionNotFoundException;
use App\Sale\Domain\Exception\InvalidDinerCountException;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateChargeSessionDiners
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(UpdateChargeSessionDinersCommand $command): UpdateChargeSessionDinersResponse
    {
        if ($command->newDinersCount <= 0) {
            throw InvalidDinerCountException::create();
        }

        $sessionUuid = Uuid::create($command->chargeSessionId);

        $session = $this->chargeSessionRepository->findById($sessionUuid)
            ?? throw ChargeSessionNotFoundException::withId($command->chargeSessionId);

        [$totalCents, $paidCents, $paidDinerNumbers] = $this->responseBuilder->collect($session);
        $dinersBefore = $session->dinersCount();

        $session->updateDinersCount($command->newDinersCount, count($paidDinerNumbers));

        $this->chargeSessionRepository->save($session);

        $this->eventBus->publish(new ChargeSessionDinersUpdated(
            chargeSessionId: $session->id()->value(),
            dinersBefore: $dinersBefore,
            dinersAfter: $command->newDinersCount,
        ));

        return UpdateChargeSessionDinersResponse::fromLiveDebt($session, $totalCents, $paidCents, $paidDinerNumbers);
    }
}
