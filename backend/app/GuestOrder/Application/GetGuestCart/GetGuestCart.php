<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetGuestCart;

use App\GuestOrder\Domain\Exception\GuestSessionNotFoundException;
use App\GuestOrder\Domain\Interfaces\GuestOrderLineRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\GuestSessionRepositoryInterface;

final class GetGuestCart
{
    public function __construct(
        private readonly GuestSessionRepositoryInterface $guestSessionRepository,
        private readonly GuestOrderLineRepositoryInterface $lineRepository,
    ) {}

    public function __invoke(GetGuestCartCommand $command): GetGuestCartResponse
    {
        $session = $this->guestSessionRepository->findBySessionToken($command->sessionToken);
        if ($session === null || $session->isExpired()) {
            throw GuestSessionNotFoundException::withToken($command->sessionToken);
        }

        $lines = $this->lineRepository->getPendingLines($session->id()->value());

        return GetGuestCartResponse::create($lines);
    }
}
