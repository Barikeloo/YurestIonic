<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateChargeSession;

use App\Sale\Domain\Entity\ChargeSession;

final class CreateChargeSessionResponse
{
    private function __construct(
        public readonly string $id,
        public readonly string $orderId,
        public readonly int $dinersCount,
        public readonly int $totalCents,
        public readonly int $paidCents,
        public readonly int $remainingCents,
        public readonly int $suggestedPerDinerCents,
        public readonly array $paidDinerNumbers,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public static function fromLiveDebt(
        ChargeSession $session,
        int $totalCents,
        int $paidCents,
        array $paidDinerNumbers,
    ): self {
        $remainingCents = max(0, $totalCents - $paidCents);
        $pendingDiners = max(0, $session->dinersCount() - count($paidDinerNumbers));
        $suggested = 0;
        if ($pendingDiners > 0 && $remainingCents > 0) {
            $suggested = $pendingDiners === 1
                ? $remainingCents
                : (int) floor($remainingCents / $pendingDiners);
        }

        return new self(
            id: $session->id()->value(),
            orderId: $session->orderId()->value(),
            dinersCount: $session->dinersCount(),
            totalCents: $totalCents,
            paidCents: $paidCents,
            remainingCents: $remainingCents,
            suggestedPerDinerCents: $suggested,
            paidDinerNumbers: array_values(array_unique($paidDinerNumbers)),
            status: $session->status()->value(),
            createdAt: $session->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $session->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->orderId,
            'diners_count' => $this->dinersCount,
            'total_cents' => $this->totalCents,
            'paid_cents' => $this->paidCents,
            'remaining_cents' => $this->remainingCents,
            'suggested_per_diner_cents' => $this->suggestedPerDinerCents,
            'paid_diner_numbers' => $this->paidDinerNumbers,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
