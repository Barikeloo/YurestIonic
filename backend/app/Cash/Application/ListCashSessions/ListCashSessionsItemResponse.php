<?php

declare(strict_types=1);

namespace App\Cash\Application\ListCashSessions;

final readonly class ListCashSessionsItemResponse
{
    private function __construct(
        public string $uuid,
        public string $deviceId,
        public string $openedByUserId,
        public ?string $closedByUserId,
        public ?string $openedAt,
        public ?string $closedAt,
        public int $initialAmountCents,
        public ?int $finalAmountCents,
        public ?int $expectedAmountCents,
        public ?int $discrepancyCents,
        public ?string $discrepancyReason,
        public ?int $zReportNumber,
        public string $status,
        public int $tickets,
        public int $diners,
        public int $gross,
        public int $discounts,
        public int $invitations,
        public int $invValue,
        public int $cancellations,
        public int $net,
        public int $movIn,
        public int $movOut,
    ) {}

    public static function create(
        string $uuid,
        string $deviceId,
        string $openedByUserId,
        ?string $closedByUserId,
        ?string $openedAt,
        ?string $closedAt,
        int $initialAmountCents,
        ?int $finalAmountCents,
        ?int $expectedAmountCents,
        ?int $discrepancyCents,
        ?string $discrepancyReason,
        ?int $zReportNumber,
        string $status,
        int $tickets,
        int $diners,
        int $gross,
        int $discounts,
        int $invitations,
        int $invValue,
        int $cancellations,
        int $net,
        int $movIn,
        int $movOut,
    ): self {
        return new self(
            uuid: $uuid,
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
            status: $status,
            tickets: $tickets,
            diners: $diners,
            gross: $gross,
            discounts: $discounts,
            invitations: $invitations,
            invValue: $invValue,
            cancellations: $cancellations,
            net: $net,
            movIn: $movIn,
            movOut: $movOut,
        );
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
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
            'status' => $this->status,
            'tickets' => $this->tickets,
            'diners' => $this->diners,
            'gross' => $this->gross,
            'discounts' => $this->discounts,
            'invitations' => $this->invitations,
            'inv_value' => $this->invValue,
            'cancellations' => $this->cancellations,
            'net' => $this->net,
            'mov_in' => $this->movIn,
            'mov_out' => $this->movOut,
        ];
    }
}
