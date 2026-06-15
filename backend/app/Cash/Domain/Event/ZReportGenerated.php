<?php

declare(strict_types=1);

namespace App\Cash\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ZReportGenerated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $zReportId,
        private int $reportNumber,
        private string $totalSalesFormatted,
        private string $totalCashFormatted,
        private string $totalCardFormatted,
        private int $salesCount,
        private int $cancelledSalesCount,
        private string $discrepancyFormatted,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'caja.z_report_generated';
    }

    public function auditEntityType(): string
    {
        return 'z_report';
    }

    public function auditEntityId(): string
    {
        return $this->zReportId;
    }

    public function auditMetadata(): array
    {
        return [
            'report_number' => $this->reportNumber,
            'total_sales_formatted' => $this->totalSalesFormatted,
            'total_cash_formatted' => $this->totalCashFormatted,
            'total_card_formatted' => $this->totalCardFormatted,
            'sales_count' => $this->salesCount,
            'cancelled_sales_count' => $this->cancelledSalesCount,
            'discrepancy_formatted' => $this->discrepancyFormatted,
        ];
    }

    public function auditBefore(): ?array
    {
        return null;
    }

    public function auditAfter(): ?array
    {
        return null;
    }
}
