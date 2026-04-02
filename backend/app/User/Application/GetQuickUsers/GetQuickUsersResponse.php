<?php

namespace App\User\Application\GetQuickUsers;

class GetQuickUsersResponse
{
    /**
     * @param array<int, array<string, mixed>> $users
     */
    private function __construct(private array $users) {}

    public static function create(array $users): self
    {
        return new self($users);
    }

    public function toArray(): array
    {
        return [
            'users' => $this->users,
        ];
    }
}
