<?php

namespace App\User\Application\GetQuickUsers;

final readonly class GetQuickUsersResponse
{

    private function __construct(private array $users) {}

    public static function create(array $users): self
    {
        return new self(
            users: $users,
        );
    }

    public function toArray(): array
    {
        return [
            'users' => $this->users,
        ];
    }
}
