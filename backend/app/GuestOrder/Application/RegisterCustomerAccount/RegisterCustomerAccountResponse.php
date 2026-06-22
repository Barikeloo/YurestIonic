<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\RegisterCustomerAccount;

final readonly class RegisterCustomerAccountResponse
{
    private function __construct(
        public string $customerId,
        public string $name,
        public string $email,
        public int $points,
        public int $visitsCount,
        public string $customerAuthToken,
    ) {}

    public static function create(
        string $customerId,
        string $name,
        string $email,
        int $points,
        int $visitsCount,
        string $customerAuthToken,
    ): self {
        return new self(
            customerId: $customerId,
            name: $name,
            email: $email,
            points: $points,
            visitsCount: $visitsCount,
            customerAuthToken: $customerAuthToken,
        );
    }

    public function toArray(): array
    {
        return [
            'customer' => [
                'id'           => $this->customerId,
                'name'         => $this->name,
                'email'        => $this->email,
                'points'       => $this->points,
                'visits_count' => $this->visitsCount,
                'active_offers' => [],
            ],
            'customer_auth_token' => $this->customerAuthToken,
        ];
    }
}
