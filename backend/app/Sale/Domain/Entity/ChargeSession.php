<?php

declare(strict_types=1);

namespace App\Sale\Domain\Entity;

use App\Sale\Domain\ValueObject\AmountPerDiner;
use App\Sale\Domain\ValueObject\ChargeSessionStatus;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

/**
 * ChargeSession - Sesión de cobro para pago a partes iguales
 *
 * Según la especificación:
 * - La cuota se calcula una vez al crear la sesión
 * - Nunca se recalcula automáticamente
 * - Solo se puede editar diners si no hay pagos (paidCount === 0)
 */
final class ChargeSession
{
    /** @var array<ChargeSessionPayment> */
    private array $payments = [];

    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $orderId,
        private readonly Uuid $openedByUserId,
        private int $dinersCount,
        private readonly int $totalCents,
        private AmountPerDiner $amountPerDiner,
        private int $paidDinersCount,
        private ChargeSessionStatus $status,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt = null,
        private ?Uuid $cancelledByUserId = null,
        private ?string $cancellationReason = null,
        private ?DomainDateTime $cancelledAt = null,
    ) {}

    /**
     * Factory method para crear nueva sesión de cobro
     */
    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $orderId,
        Uuid $openedByUserId,
        int $dinersCount,
        int $totalCents,
    ): self {
        if ($dinersCount <= 0) {
            throw new \DomainException('Diners count must be greater than 0');
        }

        if ($totalCents < 0) {
            throw new \DomainException('Total cannot be negative');
        }

        $amountPerDiner = AmountPerDiner::create($totalCents, $dinersCount);

        return new self(
            id: $id,
            restaurantId: $restaurantId,
            orderId: $orderId,
            openedByUserId: $openedByUserId,
            dinersCount: $dinersCount,
            totalCents: $totalCents,
            amountPerDiner: $amountPerDiner,
            paidDinersCount: 0,
            status: ChargeSessionStatus::active(),
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    /**
     * Factory method para reconstruir desde persistencia
     */
    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $orderId,
        string $openedByUserId,
        int $dinersCount,
        int $totalCents,
        int $amountPerDiner,
        int $paidDinersCount,
        string $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt = null,
        ?string $cancelledByUserId = null,
        ?string $cancellationReason = null,
        ?\DateTimeImmutable $cancelledAt = null,
    ): self {
        return new self(
            id: Uuid::create($id),
            restaurantId: Uuid::create($restaurantId),
            orderId: Uuid::create($orderId),
            openedByUserId: Uuid::create($openedByUserId),
            dinersCount: $dinersCount,
            totalCents: $totalCents,
            amountPerDiner: AmountPerDiner::fromInt($amountPerDiner),
            paidDinersCount: $paidDinersCount,
            status: ChargeSessionStatus::create($status),
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
            deletedAt: $deletedAt !== null ? DomainDateTime::create($deletedAt) : null,
            cancelledByUserId: $cancelledByUserId !== null ? Uuid::create($cancelledByUserId) : null,
            cancellationReason: $cancellationReason,
            cancelledAt: $cancelledAt !== null ? DomainDateTime::create($cancelledAt) : null,
        );
    }

    /**
     * Registrar un pago de un comensal
     *
     * @throws \DomainException si la sesión no está activa o el comensal ya pagó
     */
    public function recordPayment(Uuid $paymentId, int $dinerNumber, string $paymentMethod): ChargeSessionPayment
    {
        if (! $this->status->isActive()) {
            throw new \DomainException('Cannot record payment: session is not active');
        }

        if ($dinerNumber < 1 || $dinerNumber > $this->dinersCount) {
            throw new \DomainException('Invalid diner number');
        }

        // Check if this diner already paid
        foreach ($this->payments as $payment) {
            if ($payment->dinerNumber() === $dinerNumber && $payment->isCompleted()) {
                throw new \DomainException("Diner {$dinerNumber} has already paid");
            }
        }

        // Calculate amount (last diner pays the remainder)
        $amount = $this->amountPerDiner->calculateForDiner(
            $dinerNumber,
            $this->dinersCount,
            $this->totalCents
        );

        $payment = ChargeSessionPayment::create(
            $paymentId,
            $this->id,
            $dinerNumber,
            $amount,
            $paymentMethod
        );

        $this->payments[] = $payment;
        $this->paidDinersCount++;
        $this->updatedAt = DomainDateTime::now();

        // Check if all diners have paid
        if ($this->paidDinersCount === $this->dinersCount) {
            $this->status = ChargeSessionStatus::completed();
        }

        return $payment;
    }

    /**
     * Modificar el número de comensales
     *
     * @throws \DomainException si ya hay pagos registrados
     */
    public function updateDinersCount(int $newDinersCount): void
    {
        if (! $this->status->isActive()) {
            throw new \DomainException('Cannot modify diners: session is not active');
        }

        if ($this->paidDinersCount > 0) {
            throw new \DomainException(
                "Cannot modify diners: {$this->paidDinersCount} payment(s) already recorded. ".
                'Cancel the session and create a new one if needed.'
            );
        }

        if ($newDinersCount <= 0) {
            throw new \DomainException('Diners count must be greater than 0');
        }

        $this->dinersCount = $newDinersCount;
        $this->amountPerDiner = AmountPerDiner::create($this->totalCents, $newDinersCount);
        $this->updatedAt = DomainDateTime::now();
    }

    /**
     * Cancelar la sesión
     */
    public function cancel(Uuid $cancelledByUserId, ?string $reason = null): void
    {
        if (! $this->status->isActive()) {
            throw new \DomainException('Cannot cancel: session is not active');
        }

        $this->status = ChargeSessionStatus::cancelled();
        $this->cancelledByUserId = $cancelledByUserId;
        $this->cancellationReason = $reason;
        $this->cancelledAt = DomainDateTime::now();
        $this->updatedAt = DomainDateTime::now();
    }

    /**
     * Verificar si se puede modificar el número de comensales
     */
    public function canEditDinersCount(): bool
    {
        return $this->status->isActive() && $this->paidDinersCount === 0;
    }

    // Getters

    public function id(): Uuid
    {
        return $this->id;
    }

    public function restaurantId(): Uuid
    {
        return $this->restaurantId;
    }

    public function orderId(): Uuid
    {
        return $this->orderId;
    }

    public function openedByUserId(): Uuid
    {
        return $this->openedByUserId;
    }

    public function dinersCount(): int
    {
        return $this->dinersCount;
    }

    public function totalCents(): int
    {
        return $this->totalCents;
    }

    public function amountPerDiner(): AmountPerDiner
    {
        return $this->amountPerDiner;
    }

    public function paidDinersCount(): int
    {
        return $this->paidDinersCount;
    }

    public function status(): ChargeSessionStatus
    {
        return $this->status;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DomainDateTime
    {
        return $this->deletedAt;
    }

    public function cancelledByUserId(): ?Uuid
    {
        return $this->cancelledByUserId;
    }

    public function cancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function cancelledAt(): ?DomainDateTime
    {
        return $this->cancelledAt;
    }

    /**
     * Cargar pagos desde persistencia (solo para repositorios)
     *
     * @param  array<ChargeSessionPayment>  $payments
     */
    public function loadPayments(array $payments): void
    {
        $this->payments = $payments;
    }

    /**
     * @return array<ChargeSessionPayment>
     */
    public function payments(): array
    {
        return $this->payments;
    }

    /**
     * Calcular el total pendiente por cobrar
     */
    public function remainingAmount(): int
    {
        $paidAmount = 0;
        foreach ($this->payments as $payment) {
            if ($payment->isCompleted()) {
                $paidAmount += $payment->amount();
            }
        }

        return $this->totalCents - $paidAmount;
    }
}
