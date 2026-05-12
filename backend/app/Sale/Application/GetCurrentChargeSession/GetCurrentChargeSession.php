<?php

declare(strict_types=1);

namespace App\Sale\Application\GetCurrentChargeSession;

use App\Sale\Application\CreateChargeSession\ChargeSessionResponseBuilder;
use App\Sale\Domain\Exception\ChargeSessionNotFoundException;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetCurrentChargeSession
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
    ) {}

    public function __invoke(GetCurrentChargeSessionCommand $command): GetCurrentChargeSessionResponse
    {
        $orderId = Uuid::create($command->orderId);

        $session = $this->chargeSessionRepository->findCurrentByOrderId($orderId);

        if ($session === null) {
            throw ChargeSessionNotFoundException::withId($command->orderId);
        }

        $response = $this->responseBuilder->build($session);

        return GetCurrentChargeSessionResponse::fromPayload($response->toArray());
    }
}
