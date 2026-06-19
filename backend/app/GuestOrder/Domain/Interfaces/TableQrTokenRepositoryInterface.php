<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Interfaces;

use App\GuestOrder\Domain\Entity\TableQrToken;
use App\GuestOrder\Domain\ReadModel\TableStatusData;

interface TableQrTokenRepositoryInterface
{
    public function findByToken(string $token): ?TableQrToken;

    public function findByTableId(string $tableId): ?TableQrToken;

    public function save(TableQrToken $tableQrToken): void;

    public function findStatusByToken(string $token): ?TableStatusData;
}
