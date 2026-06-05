<?php

declare(strict_types=1);

namespace App\Audit\Domain\Interfaces;

use App\Audit\Domain\ValueObject\VerifyChainResult;
use App\Shared\Domain\ValueObject\Uuid;

interface VerifyChainResultRepositoryInterface
{
    public function save(VerifyChainResult $result): void;

    public function latestByRestaurant(Uuid $restaurantId): ?VerifyChainResult;
}
