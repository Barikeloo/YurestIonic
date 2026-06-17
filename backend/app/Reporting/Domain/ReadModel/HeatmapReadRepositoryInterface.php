<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ReadModel;

interface HeatmapReadRepositoryInterface
{
    public function getHeatmap(int $restaurantId): array;
}
