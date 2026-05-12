<?php

declare(strict_types=1);

namespace App\Cash\Application\RegisterCashMovement;

use App\Cash\Domain\Entity\CashMovement;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Exception\CashSessionNotOpenForMovementException;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\ValueObject\MovementReasonCode;
use App\Cash\Domain\ValueObject\MovementType;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class RegisterCashMovement
{
    public function __construct(
        private readonly CashMovementRepositoryInterface $cashMovementRepository,
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
    ) {}

    public function __invoke(RegisterCashMovementCommand $command): RegisterCashMovementResponse
    {
        $cashSessionUuid = Uuid::create($command->cashSessionId);

        $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid)
            ?? throw CashSessionNotFoundException::withId($command->cashSessionId);

        if (! $cashSession->status()->isOpen()) {
            throw new CashSessionNotOpenForMovementException;
        }

        $cashMovement = CashMovement::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($command->restaurantId),
            cashSessionId: $cashSessionUuid,
            type: MovementType::create($command->type),
            reasonCode: MovementReasonCode::create($command->reasonCode),
            amount: Money::create($command->amountCents),
            userId: Uuid::create($command->userId),
            description: $command->description,
        );

        $this->cashMovementRepository->save($cashMovement);

        return RegisterCashMovementResponse::create(
            id: $cashMovement->id()->value(),
            uuid: $cashMovement->uuid()->value(),
            restaurantId: $cashMovement->restaurantId()->value(),
            cashSessionId: $cashMovement->cashSessionId()->value(),
            type: $cashMovement->type()->value(),
            reasonCode: $cashMovement->reasonCode()->value(),
            amountCents: $cashMovement->amount()->toCents(),
            description: $cashMovement->description(),
            userId: $cashMovement->userId()->value(),
        );
    }
}
