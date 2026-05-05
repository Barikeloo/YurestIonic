<?php

declare(strict_types=1);

namespace App\Sale\Application\UpdateChargeSessionDiners;

use App\Sale\Application\CreateChargeSession\ChargeSessionResponseBuilder;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateChargeSessionDiners
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
    ) {}

    public function __invoke(
        string $chargeSessionId,
        int $newDinersCount,
    ): UpdateChargeSessionDinersResponse {
        if ($newDinersCount <= 0) {
            throw new \DomainException('Diners count must be greater than 0');
        }

        $sessionUuid = Uuid::create($chargeSessionId);

        $session = $this->chargeSessionRepository->findById($sessionUuid);

        if ($session === null) {
            throw new \DomainException('Charge session not found');
        }

        [$totalCents, $paidCents, $paidDinerNumbers] = $this->responseBuilder->collect($session);

        $session->updateDinersCount($newDinersCount, count($paidDinerNumbers));

        $this->chargeSessionRepository->save($session);

        return UpdateChargeSessionDinersResponse::fromLiveDebt($session, $totalCents, $paidCents, $paidDinerNumbers);
    }
}
