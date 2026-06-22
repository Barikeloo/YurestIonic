<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\RequestCheck;

use App\GuestOrder\Domain\Event\CheckRequestedByGuest;
use App\GuestOrder\Domain\Exception\GuestSessionNotFoundException;
use App\GuestOrder\Domain\Exception\TableToChargeException;
use App\GuestOrder\Domain\Interfaces\GuestSessionRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use Illuminate\Support\Facades\DB;

final class RequestCheck
{
    public function __construct(
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
        private readonly GuestSessionRepositoryInterface $guestSessionRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(RequestCheckCommand $command): RequestCheckResponse
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
        if ($order === null) {
            throw GuestSessionNotFoundException::withToken($command->sessionToken);
        }

        if ($order->status()->isToCharge()) {
            throw TableToChargeException::create();
        }

        $now = new \DateTimeImmutable();

        DB::table('guest_sessions')
            ->where('uuid', $session->id()->value())
            ->update(['check_requested_at' => $now]);

        $this->eventBus->publish(new CheckRequestedByGuest(
            guestSessionId: $session->id()->value(),
            orderId: $order->id()->value(),
            restaurantId: $qrToken->restaurantId()->value(),
            tableId: $qrToken->tableId()->value(),
            guestName: $session->guestName(),
            requestedAt: $now->format(\DateTimeInterface::ATOM),
        ));

        return RequestCheckResponse::create($now->format(\DateTimeInterface::ATOM));
    }
}
