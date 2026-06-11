<?php

declare(strict_types=1);

namespace App\Reporting\Application\ListReportExports;

use App\Reporting\Domain\Interfaces\ReportExportRepositoryInterface;

final readonly class ListReportExports
{
    public function __construct(
        private ReportExportRepositoryInterface $repository,
    ) {}

    public function __invoke(ListReportExportsCommand $command): ListReportExportsResponse
    {
        $items = $this->repository->listRecent(
            $command->restaurantId,
            $command->days,
            $command->limit,
        );

        return ListReportExportsResponse::create($items);
    }
}
