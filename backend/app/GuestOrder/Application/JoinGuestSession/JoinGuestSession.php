<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\JoinGuestSession;

use App\GuestOrder\Domain\Entity\GuestSession;
use App\GuestOrder\Domain\Exception\TableNotOpenException;
use App\GuestOrder\Domain\Exception\TableQrTokenNotFoundException;
use App\GuestOrder\Domain\Exception\TableToChargeException;
use App\GuestOrder\Domain\Interfaces\GuestSessionRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;
use App\GuestOrder\Domain\ValueObject\GuestSessionToken;
use App\GuestOrder\Domain\ValueObject\IdentityMode;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;

final class JoinGuestSession
{
    public function __construct(
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly GuestSessionRepositoryInterface $guestSessionRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(JoinGuestSessionCommand $command): JoinGuestSessionResponse
    {
        $qrToken = $this->tableQrTokenRepository->findByToken($command->token)
            ?? throw TableQrTokenNotFoundException::withToken($command->token);

        $order = $this->orderRepository->findActiveByTableId($qrToken->tableId())
            ?? throw TableNotOpenException::forToken($command->token);

        if ($order->status()->isToCharge()) {
            throw TableToChargeException::create();
        }

        $session = GuestSession::dddCreateAsJoiner(
            tableQrTokenId: $qrToken->id(),
            restaurantId: $qrToken->restaurantId(),
            orderId: $order->id(),
            sessionToken: GuestSessionToken::create($command->sessionToken),
            identityMode: IdentityMode::create($command->identityMode),
            guestName: $command->guestName,
        );

        $this->guestSessionRepository->save($session);
        $this->eventBus->publish(...$session->pullDomainEvents());

        return JoinGuestSessionResponse::create(
            sessionId: $session->id()->value(),
            sessionToken: $session->sessionToken()->value(),
            orderId: $order->id()->value(),
            identityMode: $session->identityMode()->value(),
            guestName: $session->guestName(),
            expiresAt: $session->expiresAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
