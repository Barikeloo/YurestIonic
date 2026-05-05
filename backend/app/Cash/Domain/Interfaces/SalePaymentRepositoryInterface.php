<?php

declare(strict_types=1);

namespace App\Cash\Domain\Interfaces;

use App\Sale\Domain\Entity\SalePayment;
use App\Shared\Domain\ValueObject\Uuid;

interface SalePaymentRepositoryInterface
{
    public function save(SalePayment $salePayment): void;

    public function getById(string $id): ?SalePayment;

    public function findById(Uuid $id): ?SalePayment;

    public function findByUuid(Uuid $uuid): ?SalePayment;

    public function findBySaleId(Uuid $saleId): array;

    public function findByCashSessionId(Uuid $cashSessionId): array;

    public function findNonCancelledByCashSessionId(Uuid $cashSessionId): array;

    public function findByChargeSessionId(Uuid $chargeSessionId): array;

    public function delete(Uuid $id): void;
}
