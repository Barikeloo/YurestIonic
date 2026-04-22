<?php

declare(strict_types=1);

namespace App\Cash\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class ZReport
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $cashSessionId,
        private readonly int $reportNumber,
        private readonly string $reportHash,
        private readonly Money $totalSales,
        private readonly Money $totalCash,
        private readonly Money $totalCard,
        private readonly Money $totalOther,
        private readonly Money $cashIn,
        private readonly Money $cashOut,
        private readonly Money $tips,
        private readonly Money $discrepancy,
        private readonly int $salesCount,
        private readonly int $cancelledSalesCount,
        private readonly DomainDateTime $generatedAt,
    ) {
    }

    public static function generate(
        Uuid $cashSessionId,
        int $reportNumber,
        Money $totalSales,
        Money $totalCash,
        Money $totalCard,
        Money $totalOther,
        Money $cashIn,
        Money $cashOut,
        Money $tips,
        Money $discrepancy,
        int $salesCount,
        int $cancelledSalesCount,
    ): self {
        $id = Uuid::generate();
        $reportHash = self::calculateHash(
            $cashSessionId,
            $reportNumber,
            $totalSales,
            $totalCash,
            $totalCard,
            $totalOther,
            $cashIn,
            $cashOut,
            $tips,
            $discrepancy,
            $salesCount,
            $cancelledSalesCount,
        );

        return new self(
            id: $id,
            cashSessionId: $cashSessionId,
            reportNumber: $reportNumber,
            reportHash: $reportHash,
            totalSales: $totalSales,
            totalCash: $totalCash,
            totalCard: $totalCard,
            totalOther: $totalOther,
            cashIn: $cashIn,
            cashOut: $cashOut,
            tips: $tips,
            discrepancy: $discrepancy,
            salesCount: $salesCount,
            cancelledSalesCount: $cancelledSalesCount,
            generatedAt: DomainDateTime::now(),
        );
    }

    private static function calculateHash(
        Uuid $cashSessionId,
        int $reportNumber,
        Money $totalSales,
        Money $totalCash,
        Money $totalCard,
        Money $totalOther,
        Money $cashIn,
        Money $cashOut,
        Money $tips,
        Money $discrepancy,
        int $salesCount,
        int $cancelledSalesCount,
    ): string {
        $data = implode('|', [
            $cashSessionId->value(),
            $reportNumber,
            $totalSales->toCents(),
            $totalCash->toCents(),
            $totalCard->toCents(),
            $totalOther->toCents(),
            $cashIn->toCents(),
            $cashOut->toCents(),
            $tips->toCents(),
            $discrepancy->toCents(),
            $salesCount,
            $cancelledSalesCount,
            DomainDateTime::now()->format('Y-m-d H:i:s'),
        ]);

        return hash('sha256', $data);
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function cashSessionId(): Uuid
    {
        return $this->cashSessionId;
    }

    public function reportNumber(): int
    {
        return $this->reportNumber;
    }

    public function reportHash(): string
    {
        return $this->reportHash;
    }

    public function totalSales(): Money
    {
        return $this->totalSales;
    }

    public function totalCash(): Money
    {
        return $this->totalCash;
    }

    public function totalCard(): Money
    {
        return $this->totalCard;
    }

    public function totalOther(): Money
    {
        return $this->totalOther;
    }

    public function cashIn(): Money
    {
        return $this->cashIn;
    }

    public function cashOut(): Money
    {
        return $this->cashOut;
    }

    public function tips(): Money
    {
        return $this->tips;
    }

    public function discrepancy(): Money
    {
        return $this->discrepancy;
    }

    public function salesCount(): int
    {
        return $this->salesCount;
    }

    public function cancelledSalesCount(): int
    {
        return $this->cancelledSalesCount;
    }

    public function generatedAt(): DomainDateTime
    {
        return $this->generatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'cash_session_id' => $this->cashSessionId->value(),
            'report_number' => $this->reportNumber,
            'report_hash' => $this->reportHash,
            'total_sales_cents' => $this->totalSales->toCents(),
            'total_cash_cents' => $this->totalCash->toCents(),
            'total_card_cents' => $this->totalCard->toCents(),
            'total_other_cents' => $this->totalOther->toCents(),
            'cash_in_cents' => $this->cashIn->toCents(),
            'cash_out_cents' => $this->cashOut->toCents(),
            'tips_cents' => $this->tips->toCents(),
            'discrepancy_cents' => $this->discrepancy->toCents(),
            'sales_count' => $this->salesCount,
            'cancelled_sales_count' => $this->cancelledSalesCount,
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
