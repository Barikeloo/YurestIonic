<?php

declare(strict_types=1);

namespace App\Cash\Application\RegisterCashMovement;

use App\Cash\Domain\Entity\CashMovement;
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

    public function __invoke(
        string $restaurantId,
        string $cashSessionId,
        string $type,
        string $reasonCode,
        int $amountCents,
        string $userId,
        ?string $description = null,
    ): RegisterCashMovementResponse {
        $restaurantUuid = Uuid::create($restaurantId);
        $cashSessionUuid = Uuid::create($cashSessionId);

        $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid);
        if ($cashSession === null) {
            throw new \DomainException('Cash session not found.');
        }

        if (! $cashSession->status()->isOpen()) {
            throw new \DomainException('Cannot register movements on a closed session.');
        }

        $cashMovement = CashMovement::dddCreate(
            id: Uuid::generate(),
            restaurantId: $restaurantUuid,
            cashSessionId: $cashSessionUuid,
            type: MovementType::create($type),
            reasonCode: MovementReasonCode::create($reasonCode),
            amount: Money::create($amountCents),
            userId: Uuid::create($userId),
            description: $description,
        );

        $this->cashMovementRepository->save($cashMovement);

        return RegisterCashMovementResponse::create($cashMovement);
    }
}
