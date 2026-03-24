<?php

namespace App\Sale\Domain\Interfaces;

use App\Sale\Domain\Entity\Sale;
use App\Shared\Domain\ValueObject\Uuid;

interface SaleRepositoryInterface
{
    public function save(Sale $sale): void;
    public function all(): array;

    public function getById(string $id): ?Sale;


    public function findById(Uuid $id): ?Sale;

    public function findByUuid(Uuid $uuid): ?Sale;

    public function findByOrderId(Uuid $orderId): ?Sale;

    public function delete(Uuid $id): void;
}
