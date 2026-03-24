<?php

namespace Tests\Unit\Restaurant;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\Restaurant\Domain\Entity\Restaurant;
use PHPUnit\Framework\TestCase;

class RestaurantEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_with_attributes_and_vos(): void
    {
        $email = Email::create('restaurant@example.com');
        $uuid = Uuid::generate();

        $restaurant = Restaurant::dddCreate(
            $uuid,
            'Test Restaurant',
            'Test Restaurant S.L.',
            'B12345678',
            $email,
            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
        );

        $this->assertInstanceOf(Restaurant::class, $restaurant);
        $this->assertSame($uuid->value(), $restaurant->getId()->value());
        $this->assertSame('Test Restaurant', $restaurant->getName());
        $this->assertSame('Test Restaurant S.L.', $restaurant->getLegalName());
        $this->assertSame('B12345678', $restaurant->getTaxId());
        $this->assertSame('restaurant@example.com', $restaurant->getEmail()->value());
    }

    public function test_ddd_create_with_valid_uuid(): void
    {
        $email = Email::create('test@example.com');
        $uuid = Uuid::generate();

        $restaurant = Restaurant::dddCreate(
            $uuid,
            'Restaurant',
            'Restaurant S.L.',
            'B12345678',
            $email,
            'hashed_password'
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
            'Restaurant',
            'Restaurant S.L.',
            'B12345678',
            $email,
            'hashed_password'
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
