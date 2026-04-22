<?php

declare(strict_types=1);

namespace App\Cash\Application\GetZReport;

use App\Cash\Domain\Interfaces\ZReportRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetZReport
{
    public function __construct(
        private readonly ZReportRepositoryInterface $zReportRepository,
    ) {}

    public function __invoke(string $zReportId): ?GetZReportResponse
    {
        $zReportUuid = Uuid::create($zReportId);
        $zReport = $this->zReportRepository->findByUuid($zReportUuid);

        if ($zReport === null) {
            return null;
        }

        return GetZReportResponse::create($zReport);
    }
}
