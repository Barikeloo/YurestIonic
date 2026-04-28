<?php

namespace App\SuperAdmin\Application\GetSuperAdminMe;

final class GetSuperAdminMeResponse
{
    public const SUCCESS = 'success';

    public const NOT_AUTHENTICATED = 'not_authenticated';

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

    public static function notAuthenticated(): self
    {
        return new self(self::NOT_AUTHENTICATED, null, null, null);
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
