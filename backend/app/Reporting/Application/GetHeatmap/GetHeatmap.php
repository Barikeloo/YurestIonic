<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetHeatmap;

use App\Reporting\Domain\ReadModel\HeatmapReadRepositoryInterface;

final readonly class GetHeatmap
{
    public function __construct(
        private HeatmapReadRepositoryInterface $repository,
    ) {}

    public function __invoke(GetHeatmapCommand $command): GetHeatmapResponse
    {
        $data = $this->repository->getHeatmap($command->restaurantId);

        return GetHeatmapResponse::create($data);
    }
}
