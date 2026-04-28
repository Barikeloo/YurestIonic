<?php

namespace Tests\Unit\Restaurant;

use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\ValueObject\RestaurantLegalName;
use App\Restaurant\Domain\ValueObject\RestaurantName;
use App\Restaurant\Domain\ValueObject\RestaurantPasswordHash;
use App\Restaurant\Domain\ValueObject\RestaurantTaxId;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class RestaurantEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_with_attributes_and_vos(): void
    {
        $email = Email::create('restaurant@example.com');
        $uuid = Uuid::generate();

        $restaurant = Restaurant::dddCreate(
            $uuid,
            RestaurantName::create('Test Restaurant'),
            RestaurantLegalName::create('Test Restaurant S.L.'),
            RestaurantTaxId::create('B12345678'),
            $email,
            RestaurantPasswordHash::create('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
        );

        $this->assertInstanceOf(Restaurant::class, $restaurant);
        $this->assertSame($uuid->value(), $restaurant->getId()->value());
        $this->assertSame('Test Restaurant', $restaurant->getName()->value());
        $this->assertSame('Test Restaurant S.L.', $restaurant->getLegalName()?->value());
        $this->assertSame('B12345678', $restaurant->getTaxId()?->value());
        $this->assertSame('restaurant@example.com', $restaurant->getEmail()->value());
    }

    public function test_ddd_create_with_valid_uuid(): void
    {
        $email = Email::create('test@example.com');
        $uuid = Uuid::generate();

        $restaurant = Restaurant::dddCreate(
            $uuid,
            RestaurantName::create('Restaurant'),
            RestaurantLegalName::create('Restaurant S.L.'),
            RestaurantTaxId::create('B12345678'),
            $email,
            RestaurantPasswordHash::create('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
        );

        $this->assertSame($uuid->value(), $restaurant->getId()->value());
    }

    public function test_ddd_create_generates_timestamps(): void
    {
        $email = Email::create('test@example.com');
        $uuid = Uuid::generate();
        $beforeCreation = now();

        $restaurant = Restaurant::dddCreate(
            $uuid,
            RestaurantName::create('Restaurant'),
            RestaurantLegalName::create('Restaurant S.L.'),
            RestaurantTaxId::create('B12345678'),
            $email,
            RestaurantPasswordHash::create('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
        );

        $afterCreation = now();

        $this->assertTrue($restaurant->getCreatedAt()->value() >= $beforeCreation);
        $this->assertTrue($restaurant->getCreatedAt()->value() <= $afterCreation);
        $this->assertEquals(
            $restaurant->getCreatedAt()->value()->getTimestamp(),
            $restaurant->getUpdatedAt()->value()->getTimestamp()
        );
    }
}
