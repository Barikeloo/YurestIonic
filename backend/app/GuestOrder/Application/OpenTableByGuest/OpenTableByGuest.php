<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\OpenTableByGuest;

use App\GuestOrder\Domain\Entity\GuestSession;
use App\GuestOrder\Domain\Exception\TableAlreadyOpenException;
use App\GuestOrder\Domain\Exception\TableQrTokenNotFoundException;
use App\GuestOrder\Domain\Exception\TableToChargeException;
use App\GuestOrder\Domain\Interfaces\CustomerAccountRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\GuestSessionRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;
use App\GuestOrder\Domain\ValueObject\GuestSessionToken;
use App\GuestOrder\Domain\ValueObject\IdentityMode;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderDiners;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class OpenTableByGuest
{
    public function __construct(
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly GuestSessionRepositoryInterface $guestSessionRepository,
        private readonly CustomerAccountRepositoryInterface $customerAccountRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(OpenTableByGuestCommand $command): OpenTableByGuestResponse
    {
        $qrToken = $this->tableQrTokenRepository->findByToken($command->token)
            ?? throw TableQrTokenNotFoundException::withToken($command->token);

        $tableId = $qrToken->tableId();

        $existingOrder = $this->orderRepository->findActiveByTableId($tableId);

        if ($existingOrder !== null) {
            if ($existingOrder->status()->isToCharge()) {
                throw TableToChargeException::create();
            }
            throw TableAlreadyOpenException::forToken($command->token);
        }

        $customerAccountId = null;
        $guestName         = $command->guestName;

        if ($command->customerAuthToken !== null) {
            $account = $this->customerAccountRepository->findByAuthToken($command->customerAuthToken);
            if ($account !== null) {
                $customerAccountId = $account->id()->value();
                $guestName         = $account->name();
                $this->customerAccountRepository->invalidateAuthToken($command->customerAuthToken);
            }
        }

        $order = Order::dddCreate(
            id: Uuid::generate(),
            restaurantId: $qrToken->restaurantId(),
            tableId: $tableId,
            openedByUserId: null,
            diners: OrderDiners::create($command->dinersCount),
        );

        $this->orderRepository->save($order);

        $session = GuestSession::dddCreateAsTableOpener(
            tableQrTokenId: $qrToken->id(),
            restaurantId: $qrToken->restaurantId(),
            orderId: $order->id(),
            sessionToken: GuestSessionToken::create($command->sessionToken),
            identityMode: IdentityMode::create($command->identityMode),
            guestName: $guestName,
            dinersCount: $command->dinersCount,
            customerAccountId: $customerAccountId,
        );

        $this->guestSessionRepository->save($session);

        $this->eventBus->publish(
            ...$order->pullDomainEvents(),
            ...$session->pullDomainEvents(),
        );

        return OpenTableByGuestResponse::create(
            sessionId: $session->id()->value(),
            sessionToken: $session->sessionToken()->value(),
            orderId: $order->id()->value(),
            identityMode: $session->identityMode()->value(),
            guestName: $session->guestName(),
            dinersCount: $session->dinersCount() ?? $command->dinersCount,
            expiresAt: $session->expiresAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
