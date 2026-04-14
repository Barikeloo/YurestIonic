<?php

namespace Tests\Unit\User;

use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Domain\ValueObject\RestaurantName;
use App\Restaurant\Domain\ValueObject\RestaurantPasswordHash;
use App\Restaurant\Domain\ValueObject\RestaurantTaxId;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\AuthorizeRestaurantAccess\AuthorizeRestaurantAccess;
use App\User\Application\AuthorizeRestaurantAccess\AuthorizeRestaurantAccessResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class AuthorizeRestaurantAccessTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_authorizes_when_linked_and_target_share_same_tax_id(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $restaurantRepository = Mockery::mock(RestaurantRepositoryInterface::class);

        $useCase = new AuthorizeRestaurantAccess($userRepository, $restaurantRepository);

        $user = User::fromPersistence(
            id: '11111111-1111-4111-8111-111111111111',
            name: 'Admin',
            email: 'admin@example.com',
            passwordHash: '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            role: 'admin',
            restaurantId: '10',
            createdAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
            updatedAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
        );

        $linked = $this->makeRestaurant('aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa', 'B12345678');
        $target = $this->makeRestaurant('bbbbbbbb-bbbb-4bbb-bbbb-bbbbbbbbbbbb', 'B12345678');

        $userRepository->shouldReceive('findById')->once()->with('11111111-1111-4111-8111-111111111111')->andReturn($user);
        $restaurantRepository->shouldReceive('findByInternalId')->once()->with(10)->andReturn($linked);
        $restaurantRepository->shouldReceive('findByUuid')->once()->andReturn($target);

        $response = $useCase('11111111-1111-4111-8111-111111111111', 'bbbbbbbb-bbbb-4bbb-bbbb-bbbbbbbbbbbb');

        $this->assertSame(AuthorizeRestaurantAccessResponse::AUTHORIZED, $response->status());
    }

    public function test_authorizes_when_linked_restaurant_has_no_tax_id_but_target_is_same_restaurant(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $restaurantRepository = Mockery::mock(RestaurantRepositoryInterface::class);

        $useCase = new AuthorizeRestaurantAccess($userRepository, $restaurantRepository);

        $user = User::fromPersistence(
            id: '22222222-2222-4222-8222-222222222222',
            name: 'Admin',
            email: 'admin2@example.com',
            passwordHash: '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            role: 'admin',
            restaurantId: '11',
            createdAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
            updatedAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
        );

        $linked = $this->makeRestaurant('cccccccc-cccc-4ccc-8ccc-cccccccccccc', null);
        $target = $this->makeRestaurant('cccccccc-cccc-4ccc-8ccc-cccccccccccc', null);

        $userRepository->shouldReceive('findById')->once()->andReturn($user);
        $restaurantRepository->shouldReceive('findByInternalId')->once()->with(11)->andReturn($linked);
        $restaurantRepository->shouldReceive('findByUuid')->once()->andReturn($target);

        $response = $useCase('22222222-2222-4222-8222-222222222222', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc');

        $this->assertSame(AuthorizeRestaurantAccessResponse::AUTHORIZED, $response->status());
    }

    public function test_forbids_when_linked_restaurant_has_no_tax_id_and_target_is_different_restaurant(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $restaurantRepository = Mockery::mock(RestaurantRepositoryInterface::class);

        $useCase = new AuthorizeRestaurantAccess($userRepository, $restaurantRepository);

        $user = User::fromPersistence(
            id: '33333333-3333-4333-8333-333333333333',
            name: 'Admin',
            email: 'admin3@example.com',
            passwordHash: '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            role: 'admin',
            restaurantId: '12',
            createdAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
            updatedAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
        );

        $linked = $this->makeRestaurant('dddddddd-dddd-4ddd-8ddd-dddddddddddd', null);
        $target = $this->makeRestaurant('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', null);

        $userRepository->shouldReceive('findById')->once()->andReturn($user);
        $restaurantRepository->shouldReceive('findByInternalId')->once()->with(12)->andReturn($linked);
        $restaurantRepository->shouldReceive('findByUuid')->once()->andReturn($target);

        $response = $useCase('33333333-3333-4333-8333-333333333333', 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee');

        $this->assertSame(AuthorizeRestaurantAccessResponse::FORBIDDEN, $response->status());
    }

    private function makeRestaurant(string $uuid, ?string $taxId): Restaurant
    {
        return Restaurant::dddCreate(
            id: Uuid::create($uuid),
            name: RestaurantName::create('Test Restaurant'),
            legalName: null,
            taxId: $taxId !== null ? RestaurantTaxId::create($taxId) : null,
            email: Email::create('test@example.com'),
            password: RestaurantPasswordHash::create('hash'),
        );
    }
}
