<?php

declare(strict_types=1);

namespace App\Cash\Application\GetLastClosedCashSession;

final readonly class LastClosedCashSessionItemResponse
{
    private function __construct(
        public string $id,
        public string $openedByUserId,
        public ?string $closedByUserId,
        public string $openedAt,
        public ?string $closedAt,
        public ?int $finalAmountCents,
        public ?int $discrepancyCents,
        public ?string $discrepancyReason,
        public ?int $zReportNumber,
        public ?string $operatorName,
        public int $tickets,
        public int $diners,
    ) {}

    public static function create(
        string $id,
        string $openedByUserId,
        ?string $closedByUserId,
        string $openedAt,
        ?string $closedAt,
        ?int $finalAmountCents,
        ?int $discrepancyCents,
        ?string $discrepancyReason,
        ?int $zReportNumber,
        ?string $operatorName,
        int $tickets,
        int $diners,
    ): self {
        return new self(
            id: $id,
            openedByUserId: $openedByUserId,
            closedByUserId: $closedByUserId,
            openedAt: $openedAt,
            closedAt: $closedAt,
            finalAmountCents: $finalAmountCents,
            discrepancyCents: $discrepancyCents,
            discrepancyReason: $discrepancyReason,
            zReportNumber: $zReportNumber,
            operatorName: $operatorName,
            tickets: $tickets,
            diners: $diners,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'opened_by_user_id' => $this->openedByUserId,
            'closed_by_user_id' => $this->closedByUserId,
            'opened_at' => $this->openedAt,
            'closed_at' => $this->closedAt,
            'final_amount_cents' => $this->finalAmountCents,
            'discrepancy_cents' => $this->discrepancyCents,
            'discrepancy_reason' => $this->discrepancyReason,
            'z_report_number' => $this->zReportNumber,
            'operator_name' => $this->operatorName,
            'tickets' => $this->tickets,
            'diners' => $this->diners,
        ];
    }
}
