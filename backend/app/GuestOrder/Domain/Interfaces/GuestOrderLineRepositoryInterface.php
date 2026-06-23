<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Interfaces;

use App\GuestOrder\Domain\Entity\GuestSession;
use App\GuestOrder\Domain\ReadModel\CartLineData;
use App\GuestOrder\Domain\ValueObject\GuestLineInput;

interface GuestOrderLineRepositoryInterface
{
    public function savePendingLines(GuestSession $session, array $lines): array;

    public function getPendingLines(string $sessionUuid): array;

    public function getLinesByIds(array $lineUuids, string $sessionUuid): array;

    public function getAllLinesBySession(string $sessionUuid): array;

    public function markLinesAsSent(array $lineUuids, string $roundUuid): void;

  public function findPendingLineByIdAndSession(string $lineUuid, string $sessionUuid): ?CartLineData;

  public function deleteLine(string $lineUuid): void;
}
