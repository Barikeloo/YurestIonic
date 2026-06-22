<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\DeletePendingLine;

use App\GuestOrder\Domain\Exception\GuestSessionNotFoundException;
use App\GuestOrder\Domain\Exception\InvalidGuestLineException;
use App\GuestOrder\Domain\Interfaces\GuestOrderLineRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\GuestSessionRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;

final class DeletePendingLine
{
    public function __construct(
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
        private readonly GuestSessionRepositoryInterface $guestSessionRepository,
        private readonly GuestOrderLineRepositoryInterface $lineRepository,
    ) {}

    public function __invoke(DeletePendingLineCommand $command): DeletePendingLineResponse
    {
        $qrToken = $this->tableQrTokenRepository->findByToken($command->token);
        if ($qrToken === null) {
            throw GuestSessionNotFoundException::withToken($command->sessionToken);
        }

        $session = $this->guestSessionRepository->findBySessionToken($command->sessionToken);
        if ($session === null || $session->isExpired()) {
            throw GuestSessionNotFoundException::withToken($command->sessionToken);
        }

        $line = $this->lineRepository->findPendingLineByIdAndSession(
            $command->lineId,
            $session->id()->value(),
        );

        if ($line === null) {
            throw InvalidGuestLineException::lineNotFound($command->lineId);
        }

        if ($line->sendStatus === 'sent') {
            throw InvalidGuestLineException::lineAlreadySent($command->lineId);
        }

        $this->lineRepository->deleteLine($command->lineId);

        return DeletePendingLineResponse::create($command->lineId);
    }
}
