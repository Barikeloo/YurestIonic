<?php

declare(strict_types=1);

namespace App\Cash\Application\CloseCashSession;

use App\Cash\Application\GenerateZReport\GenerateZReportResponse;

final readonly class CloseCashSessionResponse
{
    private function __construct(
        public string $id,
        public string $uuid,
        public string $restaurantId,
        public string $deviceId,
        public string $openedByUserId,
        public ?string $closedByUserId,
        public string $openedAt,
        public ?string $closedAt,
        public int $initialAmountCents,
        public int $finalAmountCents,
        public int $expectedAmountCents,
        public int $discrepancyCents,
        public ?string $discrepancyReason,
        public int $zReportNumber,
        public string $zReportHash,
        public string $status,
        public GenerateZReportResponse $zReport,
    ) {}

    public static function create(
        string $id,
        string $uuid,
        string $restaurantId,
        string $deviceId,
        string $openedByUserId,
        ?string $closedByUserId,
        string $openedAt,
        ?string $closedAt,
        int $initialAmountCents,
        int $finalAmountCents,
        int $expectedAmountCents,
        int $discrepancyCents,
        ?string $discrepancyReason,
        int $zReportNumber,
        string $zReportHash,
        string $status,
        GenerateZReportResponse $zReport,
    ): self {
        return new self(
            id: $id,
            uuid: $uuid,
            restaurantId: $restaurantId,
            deviceId: $deviceId,
            openedByUserId: $openedByUserId,
            closedByUserId: $closedByUserId,
            openedAt: $openedAt,
            closedAt: $closedAt,
            initialAmountCents: $initialAmountCents,
            finalAmountCents: $finalAmountCents,
            expectedAmountCents: $expectedAmountCents,
            discrepancyCents: $discrepancyCents,
            discrepancyReason: $discrepancyReason,
            zReportNumber: $zReportNumber,
            zReportHash: $zReportHash,
            status: $status,
            zReport: $zReport,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'restaurant_id' => $this->restaurantId,
            'device_id' => $this->deviceId,
            'opened_by_user_id' => $this->openedByUserId,
            'closed_by_user_id' => $this->closedByUserId,
            'opened_at' => $this->openedAt,
            'closed_at' => $this->closedAt,
            'initial_amount_cents' => $this->initialAmountCents,
            'final_amount_cents' => $this->finalAmountCents,
            'expected_amount_cents' => $this->expectedAmountCents,
            'discrepancy_cents' => $this->discrepancyCents,
            'discrepancy_reason' => $this->discrepancyReason,
            'z_report_number' => $this->zReportNumber,
            'z_report_hash' => $this->zReportHash,
            'status' => $this->status,
            'z_report' => $this->zReport->toArray(),
        ];
    }
}
