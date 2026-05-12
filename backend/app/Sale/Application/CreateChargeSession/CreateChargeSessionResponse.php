<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateChargeSession;

use App\Sale\Domain\Entity\ChargeSession;

final readonly class CreateChargeSessionResponse
{
    private function __construct(
        public string $id,
        public string $orderId,
        public int $dinersCount,
        public int $totalCents,
        public int $paidCents,
        public int $remainingCents,
        public int $suggestedPerDinerCents,
        public array $paidDinerNumbers,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(
        string $id,
        string $orderId,
        int $dinersCount,
        int $totalCents,
        int $paidCents,
        int $remainingCents,
        int $suggestedPerDinerCents,
        array $paidDinerNumbers,
        string $status,
        string $createdAt,
        string $updatedAt,
    ): self {
        return new self(
            id: $id,
            orderId: $orderId,
            dinersCount: $dinersCount,
            totalCents: $totalCents,
            paidCents: $paidCents,
            remainingCents: $remainingCents,
            suggestedPerDinerCents: $suggestedPerDinerCents,
            paidDinerNumbers: $paidDinerNumbers,
            status: $status,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

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
