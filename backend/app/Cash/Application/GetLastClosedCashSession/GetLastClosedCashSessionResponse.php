<?php

declare(strict_types=1);

namespace App\Cash\Application\GetLastClosedCashSession;

use App\Cash\Domain\Entity\CashSession;

final readonly class GetLastClosedCashSessionResponse
{
    private function __construct(
        public ?array $lastClosed,
        public ?array $orphanSession,
    ) {}

    public static function create(
        ?CashSession $lastClosed,
        ?CashSession $orphanSession,
        ?string $operatorName = null,
        int $tickets = 0,
        int $diners = 0,
    ): self {
        return new self(
            lastClosed: $lastClosed !== null ? [
                'id' => $lastClosed->id()->value(),
                'opened_by_user_id' => $lastClosed->openedByUserId()->value(),
                'closed_by_user_id' => $lastClosed->closedByUserId()?->value(),
                'opened_at' => $lastClosed->openedAt()->format('Y-m-d H:i:s'),
                'closed_at' => $lastClosed->closedAt()?->format('Y-m-d H:i:s'),
                'final_amount_cents' => $lastClosed->finalAmount()?->toCents(),
                'discrepancy_cents' => $lastClosed->discrepancy()?->toCents(),
                'discrepancy_reason' => $lastClosed->discrepancyReason(),
                'z_report_number' => $lastClosed->zReportNumber()?->value(),
                'operator_name' => $operatorName,
                'tickets' => $tickets,
                'diners' => $diners,
            ] : null,
            orphanSession: $orphanSession !== null ? [
                'id' => $orphanSession->id()->value(),
                'opened_by_user_id' => $orphanSession->openedByUserId()->value(),
                'opened_at' => $orphanSession->openedAt()->format('Y-m-d H:i:s'),
                'device_id' => $orphanSession->deviceId()->value(),
            ] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'last_closed' => $this->lastClosed,
            'orphan_session' => $this->orphanSession,
        ];
    }
}
