<?php

declare(strict_types=1);

namespace App\Cash\Application\OpenCashSession;

use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Exception\ActiveCashSessionAlreadyExistsException;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class OpenCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
    ) {}

    public function __invoke(OpenCashSessionCommand $command): OpenCashSessionResponse
    {
        $restaurantUuid = Uuid::create($command->restaurantId);
        $device = DeviceId::create($command->deviceId);

        if ($this->cashSessionRepository->findActiveByDeviceId($device, $restaurantUuid) !== null) {
            throw ActiveCashSessionAlreadyExistsException::forDevice($command->deviceId);
        }

        $cashSession = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: $restaurantUuid,
            deviceId: $device,
            openedByUserId: Uuid::create($command->openedByUserId),
            initialAmount: Money::create($command->initialAmountCents),
            notes: $command->notes,
        );

        $this->cashSessionRepository->save($cashSession);

        return OpenCashSessionResponse::create(
            id: $cashSession->id()->value(),
            uuid: $cashSession->uuid()->value(),
            restaurantId: $cashSession->restaurantId()->value(),
            deviceId: $cashSession->deviceId()->value(),
            openedByUserId: $cashSession->openedByUserId()->value(),
            openedAt: $cashSession->openedAt()->format('Y-m-d H:i:s'),
            initialAmountCents: $cashSession->initialAmount()->toCents(),
            status: $cashSession->status()->value(),
            notes: $cashSession->notes(),
        );
    }
}
