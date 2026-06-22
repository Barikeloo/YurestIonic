<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\ValidateGuestSession;

use App\GuestOrder\Domain\Interfaces\GuestSessionRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;

final class ValidateGuestSession
{
    public function __construct(
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
        private readonly GuestSessionRepositoryInterface $guestSessionRepository,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(ValidateGuestSessionCommand $command): ValidateGuestSessionResponse
    {
        $qrToken = $this->tableQrTokenRepository->findByToken($command->token);
        if ($qrToken === null) {
            return ValidateGuestSessionResponse::invalid();
        }

        $session = $this->guestSessionRepository->findBySessionToken($command->sessionToken);

        if ($session === null || $session->isExpired()) {
            return ValidateGuestSessionResponse::invalid();
        }

        if ($session->tableQrTokenId()->value() !== $qrToken->id()->value()) {
            return ValidateGuestSessionResponse::invalid();
        }

        $order = $this->orderRepository->findActiveByTableId($qrToken->tableId());

        $orderStatus = match (true) {
            $order === null                  => 'none',
            $order->status()->isToCharge()   => 'to_charge',
            default                          => 'open',
        };

        return ValidateGuestSessionResponse::valid(
            guestName: $session->guestName(),
            identityMode: $session->identityMode()->value(),
            orderStatus: $orderStatus,
            expiresAt: $session->expiresAt()->format(\DateTimeInterface::ATOM),
            orderId: $order?->id()->value(),
        );
    }
}
