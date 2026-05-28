<?php

declare(strict_types=1);

namespace App\Cash\Application\RegisterCashMovement;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
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
        private readonly AuditRecorderInterface $auditRecorder,
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

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('caja.cash_movement'),
            entityType: 'cash_movement',
            entityId: $cashMovement->id()->value(),
            userId: Uuid::create($command->userId),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'movement_type' => $command->type,
                'amount_formatted' => number_format($command->amountCents / 100, 2).' €',
            ],
        ));

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
