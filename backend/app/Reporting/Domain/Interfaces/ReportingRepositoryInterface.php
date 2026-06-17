<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Interfaces;

use App\Reporting\Domain\ReadModel\CashReadRepositoryInterface;
use App\Reporting\Domain\ReadModel\DashboardReadRepositoryInterface;
use App\Reporting\Domain\ReadModel\EmployeesReadRepositoryInterface;
use App\Reporting\Domain\ReadModel\FamiliesReadRepositoryInterface;
use App\Reporting\Domain\ReadModel\HeatmapReadRepositoryInterface;
use App\Reporting\Domain\ReadModel\ProductsReadRepositoryInterface;
use App\Reporting\Domain\ReadModel\RestaurantInfoReadRepositoryInterface;
use App\Reporting\Domain\ReadModel\SalesReadRepositoryInterface;
use App\Reporting\Domain\ReadModel\TaxReadRepositoryInterface;

interface ReportingRepositoryInterface extends
    DashboardReadRepositoryInterface,
    RestaurantInfoReadRepositoryInterface,
    SalesReadRepositoryInterface,
    ProductsReadRepositoryInterface,
    CashReadRepositoryInterface,
    FamiliesReadRepositoryInterface,
    TaxReadRepositoryInterface,
    EmployeesReadRepositoryInterface,
    HeatmapReadRepositoryInterface
{}
