<?php

declare(strict_types=1);

namespace App\Sale\Application\UpdateChargeSessionDiners;

use App\Sale\Domain\Entity\ChargeSession;

final class UpdateChargeSessionDinersResponse
{
    public function __construct(
        public readonly string $id,
        public readonly int $dinersCount,
        public readonly int $suggestedPerDinerCents,
        public readonly int $totalCents,
        public readonly string $status,
    ) {}

    /**
     * @param array<int> $paidDinerNumbers
     */
    public static function fromLiveDebt(
        ChargeSession $chargeSession,
        int $totalCents,
        int $paidCents,
        array $paidDinerNumbers,
    ): self {
        $remainingCents = max(0, $totalCents - $paidCents);
        $pendingDinersCount = $chargeSession->dinersCount() - count($paidDinerNumbers);
        $suggestedPerDinerCents = 0;
        if ($pendingDinersCount > 0 && $remainingCents > 0) {
            $suggestedPerDinerCents = $pendingDinersCount === 1
                ? $remainingCents
                : (int) floor($remainingCents / $pendingDinersCount);
        }

        return new self(
            id: $chargeSession->id()->value(),
            dinersCount: $chargeSession->dinersCount(),
            suggestedPerDinerCents: $suggestedPerDinerCents,
            totalCents: $totalCents,
            status: $chargeSession->status()->value(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'diners_count' => $this->dinersCount,
            'suggested_per_diner_cents' => $this->suggestedPerDinerCents,
            'total_cents' => $this->totalCents,
            'status' => $this->status,
        ];
    }
}
