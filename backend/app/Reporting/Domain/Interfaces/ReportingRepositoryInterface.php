<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Interfaces;

use App\Reporting\Application\Shared\DateRange;

interface ReportingRepositoryInterface
{
    public function getDashboardData(int $restaurantId, DateRange $range): array;

    public function getRestaurantInfo(int $restaurantId): array;

    public function getFamiliesReport(int $restaurantId, DateRange $range): array;

    public function getCashReport(int $restaurantId, DateRange $range): array;

    public function getSalesList(int $restaurantId, DateRange $range, int $page, int $perPage): array;

    public function getSaleDetail(int $restaurantId, string $saleUuid): ?array;

    public function getHeatmap(int $restaurantId): array;

    public function getProductsReport(int $restaurantId, DateRange $range): array;

    public function getEmployeesReport(int $restaurantId, DateRange $range): array;

    public function getTaxReport(int $restaurantId, DateRange $range, DateRange $qRange, string $quarter, int $year): array;
}
