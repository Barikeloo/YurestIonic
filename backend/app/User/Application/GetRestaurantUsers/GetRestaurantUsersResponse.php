<?php

namespace App\User\Application\GetRestaurantUsers;

class GetRestaurantUsersResponse
{
    /**
     * @param  array<array{uuid: string, name: string, email: string, role: string}>  $users
     */
    private function __construct(
        private array $users,
    ) {}

    /**
     * @param  array<array{uuid: string, name: string, email: string, role: string}>  $users
     */
    public static function create(array $users): self
    {
        return new self($users);
    }

    /**
     * @return array<array{uuid: string, name: string, email: string, role: string}>
     */
    public function users(): array
    {
        return $this->users;
    }

    public function toArray(): array
    {
        return [
            'users' => $this->users,
        ];
    }
}
