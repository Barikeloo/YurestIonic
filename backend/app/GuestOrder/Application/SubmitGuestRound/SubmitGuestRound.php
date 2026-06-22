<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\SubmitGuestRound;

use App\GuestOrder\Domain\Entity\GuestOrderRound;
use App\GuestOrder\Domain\Exception\GuestSessionNotFoundException;
use App\GuestOrder\Domain\Exception\InvalidGuestLineException;
use App\GuestOrder\Domain\Exception\TableToChargeException;
use App\GuestOrder\Domain\Interfaces\GuestOrderLineRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\GuestOrderRoundRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\GuestSessionRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;

final class SubmitGuestRound
{
    public function __construct(
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
        private readonly GuestSessionRepositoryInterface $guestSessionRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly GuestOrderLineRepositoryInterface $lineRepository,
        private readonly GuestOrderRoundRepositoryInterface $roundRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(SubmitGuestRoundCommand $command): SubmitGuestRoundResponse
    {
        $existing = $this->roundRepository->findByIdempotencyKey($command->idempotencyKey);
        if ($existing !== null) {
            return SubmitGuestRoundResponse::create(
                roundId: $existing->id()->value(),
                roundNumber: $existing->roundNumber(),
                label: $existing->label(),
                lineCount: count($command->lineIds),
                submittedAt: $existing->submittedAt()->format(\DateTimeInterface::ATOM),
                alreadySubmitted: true,
            );
        }

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

        $lines = $this->lineRepository->getLinesByIds($command->lineIds, $session->id()->value());

        foreach ($lines as $line) {
            if ($line->sendStatus !== 'pending') {
                throw InvalidGuestLineException::lineAlreadySent($line->id);
            }
        }

        if (count($lines) !== count($command->lineIds)) {
            throw InvalidGuestLineException::lineNotFound('one or more');
        }

        $roundNumber = $this->roundRepository->getNextRoundNumber($session->id()->value());

        $tableId = $qrToken->tableId()->value();

        $round = GuestOrderRound::dddCreate(
            guestSessionId: $session->id(),
            orderId: $order->id(),
            restaurantId: $qrToken->restaurantId(),
            roundNumber: $roundNumber,
            label: $command->roundLabel,
            idempotencyKey: $command->idempotencyKey,
            tableId: $tableId,
            guestName: $session->guestName(),
            lineUuids: $command->lineIds,
        );

        $this->roundRepository->save($round);
        $this->lineRepository->markLinesAsSent($command->lineIds, $round->id()->value());
        $this->eventBus->publish(...$round->pullDomainEvents());

        return SubmitGuestRoundResponse::create(
            roundId: $round->id()->value(),
            roundNumber: $round->roundNumber(),
            label: $round->label(),
            lineCount: count($command->lineIds),
            submittedAt: $round->submittedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
