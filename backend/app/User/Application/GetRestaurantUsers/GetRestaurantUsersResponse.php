<?php

namespace App\User\Application\GetRestaurantUsers;

final readonly class GetRestaurantUsersResponse
{

    private function __construct(
        public array $users,
    ) {}

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
