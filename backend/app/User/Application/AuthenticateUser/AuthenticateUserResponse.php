<?php

namespace App\User\Application\AuthenticateUser;

use App\User\Domain\Entity\User;

final readonly class AuthenticateUserResponse
{
    private function __construct(
        public bool $success,
        public int $statusCode,
        public ?string $message,
        public ?string $id,
        public ?string $name,
        public ?string $email,
    ) {}

    public static function authenticated(User $user): self
    {
        return new self(
            success: true,
            statusCode: 200,
            message: null,
            id: $user->id()->value(),
            name: $user->name(),
            email: $user->email()->value(),
        );
    }

    public static function notFound(): self
    {
        return new self(
            success: false,
            statusCode: 404,
            message: 'User not registered.',
            id: null,
            name: null,
            email: null,
        );
    }

    public static function invalidCredentials(): self
    {
        return new self(
            success: false,
            statusCode: 401,
            message: 'Invalid credentials.',
            id: null,
            name: null,
            email: null,
        );
    }

    /**
     * @return array<string, bool|string>
     */
    public function toArray(): array
    {
        if (! $this->success) {
            return [
                'success' => false,
                'message' => (string) $this->message,
            ];
        }

        return [
            'success' => true,
            'id' => (string) $this->id,
            'name' => (string) $this->name,
            'email' => (string) $this->email,
        ];
    }
}
