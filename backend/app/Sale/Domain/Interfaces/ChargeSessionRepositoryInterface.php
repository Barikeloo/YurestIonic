<?php

declare(strict_types=1);

namespace App\Sale\Domain\Interfaces;

use App\Sale\Domain\Entity\ChargeSession;
use App\Shared\Domain\ValueObject\Uuid;

interface ChargeSessionRepositoryInterface
{
    public function save(ChargeSession $chargeSession): void;

    public function findById(Uuid $id): ?ChargeSession;

    public function findActiveByOrderId(Uuid $orderId): ?ChargeSession;

    public function findCurrentByOrderId(Uuid $orderId): ?ChargeSession;

    public function findByOrderId(Uuid $orderId): array;

    public function delete(Uuid $id): void;
}
