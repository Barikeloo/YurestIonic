<?php

namespace App\Sale\Domain\Interfaces;

use App\Sale\Domain\Entity\SaleLine;
use App\Shared\Domain\ValueObject\Uuid;

interface SaleLineRepositoryInterface
{
    public function save(SaleLine $saleLine): void;

    public function findByUuid(Uuid $uuid): ?SaleLine;

    public function findBySaleId(Uuid $saleId): array;

    public function delete(Uuid $id): void;
}
