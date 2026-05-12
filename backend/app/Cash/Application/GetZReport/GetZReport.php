<?php

declare(strict_types=1);

namespace App\Cash\Application\GetZReport;

use App\Cash\Domain\Exception\ZReportNotFoundException;
use App\Cash\Domain\Interfaces\ZReportRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetZReport
{
    public function __construct(
        private readonly ZReportRepositoryInterface $zReportRepository,
    ) {}

    public function __invoke(GetZReportCommand $command): GetZReportResponse
    {
        $zReportUuid = Uuid::create($command->zReportId);
        $zReport = $this->zReportRepository->findByUuid($zReportUuid)
            ?? throw ZReportNotFoundException::withId($command->zReportId);

        return GetZReportResponse::fromZReport($zReport);
    }
}
