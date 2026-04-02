<?php

namespace App\SuperAdmin\Application\AuthenticateSuperAdmin;

final class AuthenticateSuperAdminResponse
{
    public const SUCCESS = 'success';
    public const INVALID_CREDENTIALS = 'invalid_credentials';

    private function __construct(
        private string $status,
        private ?string $id,
        private ?string $name,
        private ?string $email,
    ) {}

    public static function success(string $id, string $name, string $email): self
    {
        return new self(self::SUCCESS, $id, $name, $email);
    }

    public static function invalidCredentials(): self
    {
        return new self(self::INVALID_CREDENTIALS, null, null, null);
    }

    public function status(): string
    {
        return $this->status;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function email(): ?string
    {
        return $this->email;
    }
}
