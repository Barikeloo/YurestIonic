<?php

declare(strict_types=1);

namespace App\Cash\Domain\Interfaces;

use App\Cash\Domain\Entity\ZReport;
use App\Cash\Domain\ValueObject\ZReportNumber;
use App\Shared\Domain\ValueObject\Uuid;

interface ZReportRepositoryInterface
{
    public function save(ZReport $zReport): void;

    public function findByUuid(Uuid $uuid): ?ZReport;

    public function findByCashSessionId(Uuid $cashSessionId): ?ZReport;

    public function nextReportNumber(Uuid $restaurantId): ZReportNumber;
}
