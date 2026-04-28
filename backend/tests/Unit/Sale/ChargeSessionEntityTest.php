<?php

declare(strict_types=1);

namespace Tests\Unit\Sale;

use App\Sale\Domain\Entity\ChargeSession;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

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
        $this->assertEquals(2500, $session->amountPerDiner()->value()); // 100/4 = 25
        $this->assertEquals(0, $session->paidDinersCount());
        $this->assertTrue($session->status()->isActive());
        $this->assertTrue($session->canEditDinersCount());
    }

    public function test_can_record_payment(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        $payment = $session->recordPayment(
            Uuid::generate(),
            1, // Diner #1
            'cash'
        );

        $this->assertEquals(1, $session->paidDinersCount());
        $this->assertEquals(2500, $payment->amount());
        $this->assertFalse($session->canEditDinersCount()); // Cannot edit after payment
    }

    public function test_last_diner_pays_remainder(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            3, // 3 diners
            10000, // 100.00 EUR - not divisible by 3
        );

        // First two diners pay 33.33 each
        $payment1 = $session->recordPayment(Uuid::generate(), 1, 'cash');
        $payment2 = $session->recordPayment(Uuid::generate(), 2, 'card');

        $this->assertEquals(3333, $payment1->amount());
        $this->assertEquals(3333, $payment2->amount());

        // Last diner pays the remainder: 100 - 33.33 - 33.33 = 33.34
        $payment3 = $session->recordPayment(Uuid::generate(), 3, 'cash');
        $this->assertEquals(3334, $payment3->amount());

        // Total should be exactly 10000
        $totalPaid = $payment1->amount() + $payment2->amount() + $payment3->amount();
        $this->assertEquals(10000, $totalPaid);
    }

    public function test_cannot_edit_diners_after_payment(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        $session->recordPayment(Uuid::generate(), 1, 'cash');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot modify diners');

        $session->updateDinersCount(3);
    }

    public function test_can_edit_diners_before_any_payment(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        $session->updateDinersCount(5);

        $this->assertEquals(5, $session->dinersCount());
        $this->assertEquals(2000, $session->amountPerDiner()->value()); // 100/5 = 20
    }

    public function test_cannot_record_payment_for_same_diner_twice(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            4,
            10000,
        );

        $session->recordPayment(Uuid::generate(), 1, 'cash');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Diner 1 has already paid');

        $session->recordPayment(Uuid::generate(), 1, 'card');
    }

    public function test_session_completes_when_all_diners_pay(): void
    {
        $session = ChargeSession::dddCreate(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            2,
            10000,
        );

        $session->recordPayment(Uuid::generate(), 1, 'cash');
        $this->assertTrue($session->status()->isActive());

        $session->recordPayment(Uuid::generate(), 2, 'card');
        $this->assertTrue($session->status()->isCompleted());
    }
}
