<?php

declare(strict_types=1);

namespace App\Cash\Domain\Entity;

use App\Cash\Domain\ValueObject\ZReportHash;
use App\Cash\Domain\ValueObject\ZReportNumber;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class ZReport
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $cashSessionId,
        private readonly ZReportNumber $reportNumber,
        private readonly ZReportHash $reportHash,
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
        Uuid $restaurantId,
        Uuid $cashSessionId,
        ZReportNumber $reportNumber,
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
        $generatedAt = DomainDateTime::now();
        $reportHash = ZReportHash::create(self::calculateHash(
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
            $generatedAt,
        ));

        return new self(
            id: $id,
            restaurantId: $restaurantId,
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
            generatedAt: $generatedAt,
        );
    }

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $cashSessionId,
        int $reportNumber,
        string $reportHash,
        int $totalSalesCents,
        int $totalCashCents,
        int $totalCardCents,
        int $totalOtherCents,
        int $cashInCents,
        int $cashOutCents,
        int $tipsCents,
        int $discrepancyCents,
        int $salesCount,
        int $cancelledSalesCount,
        \DateTimeImmutable $generatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            restaurantId: Uuid::create($restaurantId),
            cashSessionId: Uuid::create($cashSessionId),
            reportNumber: ZReportNumber::create($reportNumber),
            reportHash: ZReportHash::create($reportHash),
            totalSales: Money::create($totalSalesCents),
            totalCash: Money::create($totalCashCents),
            totalCard: Money::create($totalCardCents),
            totalOther: Money::create($totalOtherCents),
            cashIn: Money::create($cashInCents),
            cashOut: Money::create($cashOutCents),
            tips: Money::create($tipsCents),
            discrepancy: Money::create($discrepancyCents),
            salesCount: $salesCount,
            cancelledSalesCount: $cancelledSalesCount,
            generatedAt: DomainDateTime::create($generatedAt),
        );
    }

    public function verifyHash(): bool
    {
        $expected = self::calculateHash(
            $this->cashSessionId,
            $this->reportNumber,
            $this->totalSales,
            $this->totalCash,
            $this->totalCard,
            $this->totalOther,
            $this->cashIn,
            $this->cashOut,
            $this->tips,
            $this->discrepancy,
            $this->salesCount,
            $this->cancelledSalesCount,
            $this->generatedAt,
        );

        return hash_equals($expected, $this->reportHash->value());
    }

    private static function calculateHash(
        Uuid $cashSessionId,
        ZReportNumber $reportNumber,
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
        DomainDateTime $generatedAt,
    ): string {
        $data = implode('|', [
            $cashSessionId->value(),
            $reportNumber->value(),
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
            $generatedAt->format('Y-m-d H:i:s'),
        ]);

        return hash('sha256', $data);
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function restaurantId(): Uuid
    {
        return $this->restaurantId;
    }

    public function cashSessionId(): Uuid
    {
        return $this->cashSessionId;
    }

    public function reportNumber(): ZReportNumber
    {
        return $this->reportNumber;
    }

    public function reportHash(): ZReportHash
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
            'restaurant_id' => $this->restaurantId->value(),
            'cash_session_id' => $this->cashSessionId->value(),
            'report_number' => $this->reportNumber->value(),
            'report_hash' => $this->reportHash->value(),
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
