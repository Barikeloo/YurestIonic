<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetGuestOrdersHistory;

use App\GuestOrder\Domain\ReadModel\CartLineData;
use App\GuestOrder\Domain\ReadModel\RoundData;

final readonly class GetGuestOrdersHistoryResponse
{
    private function __construct(
        public array $rounds,
        public array $pendingLines,
        public int $totalSentCents,
        public int $totalPendingCents,
    ) {}

    /** @param RoundData[] $rounds @param CartLineData[] $pendingLines */
    public static function create(array $rounds, array $pendingLines): self
    {
        $sent    = 0;
        $pending = 0;

        foreach ($rounds as $r) {
            foreach ($r->lines as $l) {
                $sent += $l->unitPrice * $l->quantity;
            }
        }

        foreach ($pendingLines as $l) {
            $pending += $l->unitPrice * $l->quantity;
        }

        return new self(
            rounds: $rounds,
            pendingLines: $pendingLines,
            totalSentCents: $sent,
            totalPendingCents: $pending,
        );
    }

    public function toArray(): array
    {
        return [
            'rounds'               => array_map(fn (RoundData $r): array => [
                'round_id'     => $r->roundId,
                'round_number' => $r->roundNumber,
                'label'        => $r->label,
                'submitted_at' => $r->submittedAt,
                'lines'        => array_map(fn (CartLineData $l): array => $this->lineToArray($l), $r->lines),
            ], $this->rounds),
            'pending_lines'        => array_map(fn (CartLineData $l): array => $this->lineToArray($l), $this->pendingLines),
            'total_sent_cents'     => $this->totalSentCents,
            'total_pending_cents'  => $this->totalPendingCents,
        ];
    }

    private function lineToArray(CartLineData $l): array
    {
        return [
            'id'           => $l->id,
            'product_id'   => $l->productId,
            'product_name' => $l->productName,
            'menu_id'      => $l->menuId,
            'menu_name'    => $l->menuName,
            'variant_id'   => $l->variantId,
            'variant_name' => $l->variantName,
            'modifiers'    => $l->modifiers,
            'quantity'     => $l->quantity,
            'unit_price'   => $l->unitPrice,
            'notes'        => $l->notes,
            'send_status'  => $l->sendStatus,
        ];
    }
}
