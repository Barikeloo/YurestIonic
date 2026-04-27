<?php

declare(strict_types=1);

namespace App\Cash\Application\CloseCashSession;

use App\Cash\Domain\Entity\CashSession;

final class CloseCashSessionResponse
{
    private function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $restaurantId,
        public readonly string $deviceId,
        public readonly string $openedByUserId,
        public readonly ?string $closedByUserId,
        public readonly string $openedAt,
        public readonly ?string $closedAt,
        public readonly int $initialAmountCents,
        public readonly int $finalAmountCents,
        public readonly int $expectedAmountCents,
        public readonly int $discrepancyCents,
        public readonly ?string $discrepancyReason,
        public readonly int $zReportNumber,
        public readonly string $zReportHash,
        public readonly string $status,
        public readonly array $zReport,
    ) {}

    public static function create(CashSession $cashSession, $zReportResponse = null): self
    {
        return new self(
            id: $cashSession->id()->value(),
            uuid: $cashSession->uuid()->value(),
            restaurantId: $cashSession->restaurantId()->value(),
            deviceId: $cashSession->deviceId()->value(),
            openedByUserId: $cashSession->openedByUserId()->value(),
            closedByUserId: $cashSession->closedByUserId()?->value(),
            openedAt: $cashSession->openedAt()->format('Y-m-d H:i:s'),
            closedAt: $cashSession->closedAt()?->format('Y-m-d H:i:s'),
            initialAmountCents: $cashSession->initialAmount()->toCents(),
            finalAmountCents: $cashSession->finalAmount()?->toCents() ?? 0,
            expectedAmountCents: $cashSession->expectedAmount()?->toCents() ?? 0,
            discrepancyCents: $cashSession->discrepancy()?->toCents() ?? 0,
            discrepancyReason: $cashSession->discrepancyReason(),
            zReportNumber: $cashSession->zReportNumber()?->value() ?? 0,
            zReportHash: $cashSession->zReportHash()?->value() ?? '',
            status: $cashSession->status()->value(),
            zReport: $zReportResponse ? $zReportResponse->toArray() : [],
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
            'z_report' => $this->zReport,
        ];
    }
}
