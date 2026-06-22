<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetGuestOrdersHistory;

use App\GuestOrder\Domain\Exception\GuestSessionNotFoundException;
use App\GuestOrder\Domain\Interfaces\GuestOrderLineRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\GuestOrderRoundRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\GuestSessionRepositoryInterface;

final class GetGuestOrdersHistory
{
    public function __construct(
        private readonly GuestSessionRepositoryInterface $guestSessionRepository,
        private readonly GuestOrderRoundRepositoryInterface $roundRepository,
        private readonly GuestOrderLineRepositoryInterface $lineRepository,
    ) {}

    public function __invoke(GetGuestOrdersHistoryCommand $command): GetGuestOrdersHistoryResponse
    {
        $session = $this->guestSessionRepository->findBySessionToken($command->sessionToken);
        if ($session === null || $session->isExpired()) {
            throw GuestSessionNotFoundException::withToken($command->sessionToken);
        }

        $rounds       = $this->roundRepository->getRoundsWithLinesBySession($session->id()->value());
        $pendingLines = $this->lineRepository->getPendingLines($session->id()->value());

        return GetGuestOrdersHistoryResponse::create($rounds, $pendingLines);
    }
}
