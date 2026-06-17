<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetSaleDetail;

use App\Reporting\Domain\ReadModel\SalesReadRepositoryInterface;

final readonly class GetSaleDetail
{
    public function __construct(
        private SalesReadRepositoryInterface $repository,
    ) {}

    public function __invoke(GetSaleDetailCommand $command): GetSaleDetailResponse
    {
        $data = $this->repository->getSaleDetail(
            restaurantId: $command->restaurantId,
            saleUuid:     $command->saleUuid,
        );

        if ($data === null) {
            throw new \RuntimeException('Sale not found.');
        }

        return GetSaleDetailResponse::create($data);
    }
}
