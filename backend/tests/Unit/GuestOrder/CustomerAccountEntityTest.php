<?php

declare(strict_types=1);

namespace Tests\Unit\GuestOrder;

use App\GuestOrder\Domain\Entity\CustomerAccount;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class CustomerAccountEntityTest extends TestCase
{
    public function test_ddd_create_initializes_with_zero_points(): void
    {
        $account = CustomerAccount::dddCreate(
            restaurantId: Uuid::generate(),
            name: 'Ana García',
            email: 'ana@test.com',
            passwordHash: password_hash('password', PASSWORD_BCRYPT),
        );

        $this->assertSame(0, $account->points());
        $this->assertSame(0, $account->visitsCount());
        $this->assertSame(0, $account->totalSpentCents());
        $this->assertNull($account->lastVisitAt());
        $this->assertSame('ana@test.com', $account->email());
        $this->assertSame('Ana García', $account->name());
    }

    public function test_verify_password_returns_true_for_correct_password(): void
    {
        $hash    = password_hash('mysecretpass', PASSWORD_BCRYPT);
        $account = CustomerAccount::dddCreate(Uuid::generate(), 'Test', 'test@test.com', $hash);

        $this->assertTrue($account->verifyPassword('mysecretpass'));
    }

    public function test_verify_password_returns_false_for_wrong_password(): void
    {
        $hash    = password_hash('correctpass', PASSWORD_BCRYPT);
        $account = CustomerAccount::dddCreate(Uuid::generate(), 'Test', 'test@test.com', $hash);

        $this->assertFalse($account->verifyPassword('wrongpass'));
    }

    public function test_credit_visit_accumulates_points_and_totals(): void
    {
        $account = CustomerAccount::dddCreate(Uuid::generate(), 'Test', 'test@test.com', 'hash');

        $account->creditVisit(3500);
        $account->creditVisit(1200);

        $this->assertSame(47, $account->points());
        $this->assertSame(4700, $account->totalSpentCents());
        $this->assertSame(2, $account->visitsCount());
        $this->assertNotNull($account->lastVisitAt());
    }

    public function test_credit_visit_calculates_one_point_per_euro(): void
    {
        $account = CustomerAccount::dddCreate(Uuid::generate(), 'Test', 'test@test.com', 'hash');

        $account->creditVisit(100);
        $this->assertSame(1, $account->points());

        $account->creditVisit(99);
        $this->assertSame(1, $account->points());
    }

    public function test_generate_auth_token_produces_64_hex_chars(): void
    {
        $account = CustomerAccount::dddCreate(Uuid::generate(), 'Test', 'test@test.com', 'hash');
        $token   = $account->generateAuthToken();

        $this->assertSame(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function test_generate_auth_token_is_unique_each_call(): void
    {
        $account = CustomerAccount::dddCreate(Uuid::generate(), 'Test', 'test@test.com', 'hash');

        $this->assertNotSame($account->generateAuthToken(), $account->generateAuthToken());
    }
}
