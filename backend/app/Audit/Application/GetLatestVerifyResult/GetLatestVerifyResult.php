<?php

declare(strict_types=1);

namespace App\Audit\Application\GetLatestVerifyResult;

use App\Audit\Domain\Interfaces\VerifyChainResultRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetLatestVerifyResult
{
    public function __construct(
        private readonly VerifyChainResultRepositoryInterface $repository,
    ) {}

    public function __invoke(GetLatestVerifyResultCommand $command): GetLatestVerifyResultResponse
    {
        $restaurantId = Uuid::create($command->restaurantId);

        $result = $this->repository->latestByRestaurant($restaurantId);

        return GetLatestVerifyResultResponse::create($result);
    }
}
