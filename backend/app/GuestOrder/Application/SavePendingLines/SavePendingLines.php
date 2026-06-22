<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\SavePendingLines;

use App\GuestOrder\Domain\Exception\GuestSessionNotFoundException;
use App\GuestOrder\Domain\Exception\TableToChargeException;
use App\GuestOrder\Domain\Interfaces\GuestOrderLineRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\GuestSessionRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;

final class SavePendingLines
{
    public function __construct(
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
        private readonly GuestSessionRepositoryInterface $guestSessionRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly GuestOrderLineRepositoryInterface $lineRepository,
    ) {}

    public function __invoke(SavePendingLinesCommand $command): SavePendingLinesResponse
    {
        $qrToken = $this->tableQrTokenRepository->findByToken($command->token);
        if ($qrToken === null) {
            throw GuestSessionNotFoundException::withToken($command->sessionToken);
        }

        $session = $this->guestSessionRepository->findBySessionToken($command->sessionToken);
        if ($session === null || $session->isExpired()) {
            throw GuestSessionNotFoundException::withToken($command->sessionToken);
        }

        $order = $this->orderRepository->findActiveByTableId($qrToken->tableId());
        if ($order === null || $order->status()->isToCharge()) {
            throw TableToChargeException::create();
        }

        $lineIds = $this->lineRepository->savePendingLines($session, $command->lines);

        return SavePendingLinesResponse::create($lineIds);
    }
}
