<?php

declare(strict_types=1);

namespace Tests\Unit\Sale;

use App\Sale\Domain\Entity\ChargeSession;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

/**
 * Tests de la entidad ChargeSession con filosofía de "deuda viva".
 *
 * La entidad solo guarda snapshot (dinersCount + totalCents).
 * Los cálculos (remaining, suggested per diner) se hacen al vuelo
 * pasando los pagos acumulados como parámetro.
 */
final class ChargeSessionEntityTest extends TestCase
{
    public function test_can_create_charge_session(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4, // 4 diners
            10000, // 100.00 EUR
        );

        $this->assertEquals(4, $session->dinersCount());
        $this->assertEquals(10000, $session->totalCents());
        $this->assertTrue($session->status()->isActive());
    }

    public function test_remaining_amount_with_no_payments(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        // Sin pagos, remaining = total
        $this->assertEquals(10000, $session->remainingAmount(0));
    }

    public function test_remaining_amount_with_partial_payments(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        // Con 2500 pagados, remaining = 7500
        $this->assertEquals(7500, $session->remainingAmount(2500));

        // Con todo pagado, remaining = 0
        $this->assertEquals(0, $session->remainingAmount(10000));
    }

    public function test_remaining_amount_never_negative(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        // Si por algún razón pagamos más, no debe quedar negativo
        $this->assertEquals(0, $session->remainingAmount(12000));
    }

    public function test_amount_for_next_diner_with_no_payments(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        // 100€ / 4 = 25€ cada uno
        $this->assertEquals(2500, $session->amountForNextDiner(0, 4));
    }

    public function test_amount_for_next_diner_with_partial_payments(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        // Si ya pagaron 2500, quedan 7500 por 3 comensales = 2500
        $this->assertEquals(2500, $session->amountForNextDiner(2500, 3));
    }

    public function test_last_diner_gets_remainder(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            3,
            10000, // 100€ no divisible por 3
        );

        // Primer comensal: floor(10000/3) = 3333
        $this->assertEquals(3333, $session->amountForNextDiner(0, 3));

        // Segundo comensal (con 3333 pagados): floor(6667/2) = 3333
        $this->assertEquals(3333, $session->amountForNextDiner(3333, 2));

        // Tercer comensal (con 6666 pagados): quedan 3334
        $this->assertEquals(3334, $session->amountForNextDiner(6666, 1));
    }

    public function test_cannot_get_amount_when_no_pending_diners(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('No pending diners to charge');

        $session->amountForNextDiner(0, 0);
    }

    public function test_can_update_diners_when_no_payments(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        $session->updateDinersCount(5, 0);

        $this->assertEquals(5, $session->dinersCount());
    }

    public function test_can_update_diners_below_original_if_not_below_paid(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        // 2 comensales ya pagaron, puedo bajar a 3 o 2, pero no a 1
        $session->updateDinersCount(3, 2);
        $this->assertEquals(3, $session->dinersCount());

        $session->updateDinersCount(2, 2);
        $this->assertEquals(2, $session->dinersCount());
    }

    public function test_cannot_update_diners_below_paid_count(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot reduce diners below already-paid count');

        // 2 comensales pagaron, no puedo bajar a 1
        $session->updateDinersCount(1, 2);
    }

    public function test_cannot_update_diners_when_session_not_active(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        $session->markCompleted();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot modify diners: session is not active');

        $session->updateDinersCount(5, 0);
    }

    public function test_mark_completed_changes_status(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        $this->assertTrue($session->status()->isActive());

        $session->markCompleted();

        $this->assertTrue($session->status()->isCompleted());
    }

    public function test_mark_completed_is_idempotent(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        $session->markCompleted();
        $session->markCompleted(); // No debe lanzar excepción

        $this->assertTrue($session->status()->isCompleted());
    }

    public function test_can_cancel_active_session(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        $cancelledByUserId = Uuid::generate();

        $session->cancel($cancelledByUserId, 'Customer changed mind');

        $this->assertTrue($session->status()->isCancelled());
        $this->assertEquals($cancelledByUserId->value(), $session->cancelledByUserId()?->value());
        $this->assertEquals('Customer changed mind', $session->cancellationReason());
    }

    public function test_cannot_cancel_already_cancelled_session(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        $cancelledByUserId = Uuid::generate();
        $session->cancel($cancelledByUserId, 'First cancellation');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot cancel: session is not active');

        $session->cancel($cancelledByUserId, 'Second attempt');
    }

    public function test_cannot_cancel_completed_session(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        $session->markCompleted();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot cancel: session is not active');

        $cancelledByUserId = Uuid::generate();
        $session->cancel($cancelledByUserId, 'Attempt to cancel completed');
    }
}
