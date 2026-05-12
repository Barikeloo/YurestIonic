<?php

declare(strict_types=1);

namespace App\Sale\Application\UpdateChargeSessionDiners;

use App\Sale\Domain\Entity\ChargeSession;

final readonly class UpdateChargeSessionDinersResponse
{
    private function __construct(
        public string $id,
        public int $dinersCount,
        public int $suggestedPerDinerCents,
        public int $totalCents,
        public string $status,
    ) {}

    public static function create(
        string $id,
        int $dinersCount,
        int $suggestedPerDinerCents,
        int $totalCents,
        string $status,
    ): self {
        return new self(
            id: $id,
            dinersCount: $dinersCount,
            suggestedPerDinerCents: $suggestedPerDinerCents,
            totalCents: $totalCents,
            status: $status,
        );
    }

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
