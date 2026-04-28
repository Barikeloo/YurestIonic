<?php

declare(strict_types=1);

namespace App\Cash\Application\ListCashSessions;

use App\Cash\Domain\Entity\CashSession;

final class ListCashSessionsResponse
{
    private function __construct(private readonly array $sessions) {}

    /** @param array{session: CashSession, tickets: int, diners: int, gross: int, discounts: int, invitations: int, invValue: int, cancellations: int, net: int, movIn: int, movOut: int}[] $sessions */
    public static function create(array $sessions): self
    {
        return new self($sessions);
    }

    public function toArray(): array
    {
        return [
            'sessions' => array_map(static fn (array $data): array => [
                'uuid' => $data['session']->uuid()->value(),
                'device_id' => $data['session']->deviceId()->value(),
                'opened_by_user_id' => $data['session']->openedByUserId()->value(),
                'closed_by_user_id' => $data['session']->closedByUserId()?->value(),
                'opened_at' => $data['session']->openedAt()?->value()->format('Y-m-d\TH:i:s'),
                'closed_at' => $data['session']->closedAt()?->value()->format('Y-m-d\TH:i:s'),
                'initial_amount_cents' => $data['session']->initialAmount()->toCents(),
                'final_amount_cents' => $data['session']->finalAmount()?->toCents(),
                'expected_amount_cents' => $data['session']->expectedAmount()?->toCents(),
                'discrepancy_cents' => $data['session']->discrepancy()?->toCents(),
                'discrepancy_reason' => $data['session']->discrepancyReason(),
                'z_report_number' => $data['session']->zReportNumber()?->value(),
                'status' => $data['session']->status()->value(),
                'tickets' => $data['tickets'],
                'diners' => $data['diners'],
                'gross' => $data['gross'],
                'discounts' => $data['discounts'],
                'invitations' => $data['invitations'],
                'inv_value' => $data['invValue'],
                'cancellations' => $data['cancellations'],
                'net' => $data['net'],
                'mov_in' => $data['movIn'],
                'mov_out' => $data['movOut'],
            ], $this->sessions),
        ];
    }
}
