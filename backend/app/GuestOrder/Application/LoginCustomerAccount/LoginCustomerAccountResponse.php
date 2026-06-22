<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\LoginCustomerAccount;

final readonly class LoginCustomerAccountResponse
{
    private function __construct(
        public string $customerId,
        public string $name,
        public string $email,
        public int $points,
        public int $visitsCount,
        public ?string $lastVisitAt,
        public array $activeOffers,
        public string $customerAuthToken,
    ) {}

    public static function create(
        string $customerId,
        string $name,
        string $email,
        int $points,
        int $visitsCount,
        ?string $lastVisitAt,
        array $activeOffers,
        string $customerAuthToken,
    ): self {
        return new self(
            customerId: $customerId,
            name: $name,
            email: $email,
            points: $points,
            visitsCount: $visitsCount,
            lastVisitAt: $lastVisitAt,
            activeOffers: $activeOffers,
            customerAuthToken: $customerAuthToken,
        );
    }

    public function toArray(): array
    {
        return [
            'customer' => [
                'id'            => $this->customerId,
                'name'          => $this->name,
                'email'         => $this->email,
                'points'        => $this->points,
                'visits_count'  => $this->visitsCount,
                'last_visit_at' => $this->lastVisitAt,
                'active_offers' => $this->activeOffers,
            ],
            'customer_auth_token' => $this->customerAuthToken,
        ];
    }
}
