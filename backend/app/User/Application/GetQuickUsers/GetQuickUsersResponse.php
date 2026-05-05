<?php

namespace App\User\Application\GetQuickUsers;

final readonly class GetQuickUsersResponse
{
    /**
     * @param  array<int, array<string, mixed>>  $users
     */
    public function __construct(private array $users) {}

    public function toArray(): array
    {
        return [
            'users' => $this->users,
        ];
    }
}
