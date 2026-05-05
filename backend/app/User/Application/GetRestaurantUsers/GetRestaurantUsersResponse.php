<?php

namespace App\User\Application\GetRestaurantUsers;

final readonly class GetRestaurantUsersResponse
{
    /**
     * @param  array<array{uuid: string, name: string, email: string, role: string}>  $users
     */
    public function __construct(
        public array $users,
    ) {}

    public function toArray(): array
    {
        return [
            'users' => $this->users,
        ];
    }
}
