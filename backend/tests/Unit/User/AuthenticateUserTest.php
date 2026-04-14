<?php

namespace Tests\Unit\User;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\User\Application\AuthenticateUser\AuthenticateUser;
use App\User\Application\AuthenticateUser\AuthenticateUserResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class AuthenticateUserTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_verifies_password_using_string_hash_value_from_vo(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $restaurantRepository = Mockery::mock(RestaurantRepositoryInterface::class);
        $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

        $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

        $user = User::fromPersistence(
            id: '11111111-1111-4111-8111-111111111111',
            name: 'Auth User',
            email: 'auth@example.com',
            passwordHash: $hash,
            role: 'operator',
            restaurantId: null,
            createdAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
            updatedAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
        );

        $userRepository->shouldReceive('findByEmail')
            ->once()
            ->with('auth@example.com')
            ->andReturn($user);

        $passwordHasher->shouldReceive('verify')
            ->once()
            ->with('plain-password', $hash)
            ->andReturn(true);

        $authenticateUser = new AuthenticateUser($userRepository, $restaurantRepository, $passwordHasher);
        $response = $authenticateUser('auth@example.com', 'plain-password');

        $this->assertInstanceOf(AuthenticateUserResponse::class, $response);
        $this->assertTrue($response->success);
        $this->assertSame('Auth User', $response->name);
    }
}
