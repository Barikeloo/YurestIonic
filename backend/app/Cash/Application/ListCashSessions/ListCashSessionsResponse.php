<?php

declare(strict_types=1);

namespace App\Cash\Application\ListCashSessions;

use App\Cash\Domain\Entity\CashSession;

final class ListCashSessionsResponse
{
    private function __construct(private readonly array $sessions) {}

    /** @param CashSession[] $sessions */
    public static function create(array $sessions): self
    {
        return new self($sessions);
    }

    public function toArray(): array
    {
        return [
            'sessions' => array_map(static fn (CashSession $s): array => [
                'uuid'                  => $s->uuid()->value(),
                'device_id'             => $s->deviceId(),
                'opened_by_user_id'     => $s->openedByUserId()->value(),
                'closed_by_user_id'     => $s->closedByUserId()?->value(),
                'opened_at'             => $s->openedAt()?->value(),
                'closed_at'             => $s->closedAt()?->value(),
                'initial_amount_cents'  => $s->initialAmount()->toCents(),
                'final_amount_cents'    => $s->finalAmount()?->toCents(),
                'expected_amount_cents' => $s->expectedAmount()?->toCents(),
                'discrepancy_cents'     => $s->discrepancy()?->toCents(),
                'discrepancy_reason'    => $s->discrepancyReason(),
                'z_report_number'       => $s->zReportNumber(),
                'status'                => $s->status()->value(),
            ], $this->sessions),
        ];
    }
}
