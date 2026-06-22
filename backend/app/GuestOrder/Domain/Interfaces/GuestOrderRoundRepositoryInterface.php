<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Interfaces;

use App\GuestOrder\Domain\Entity\GuestOrderRound;
use App\GuestOrder\Domain\ReadModel\RoundData;

interface GuestOrderRoundRepositoryInterface
{
    public function save(GuestOrderRound $round): void;

    public function findByIdempotencyKey(string $key): ?GuestOrderRound;

    public function getNextRoundNumber(string $sessionUuid): int;

    /** @return RoundData[] */
    public function getRoundsWithLinesBySession(string $sessionUuid): array;
}
