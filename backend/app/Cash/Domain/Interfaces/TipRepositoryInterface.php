<?php

declare(strict_types=1);

namespace App\Cash\Domain\Interfaces;

use App\Cash\Domain\Entity\Tip;
use App\Shared\Domain\ValueObject\Uuid;

interface TipRepositoryInterface
{
    public function save(Tip $tip): void;

    public function getById(string $id): ?Tip;

    public function findById(Uuid $id): ?Tip;

    public function findByUuid(Uuid $uuid): ?Tip;

    public function findBySaleId(Uuid $saleId): array;

    public function findByCashSessionId(Uuid $cashSessionId): array;

    public function delete(Uuid $id): void;
}
