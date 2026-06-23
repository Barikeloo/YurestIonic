<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\ListCustomerAccounts;

final readonly class ListCustomerAccountsResponse
{
    private function __construct(
        public array $customers,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public static function create(array $customers, int $total, int $page, int $perPage): self
    {
        return new self(
            customers: $customers,
            total: $total,
            page: $page,
            perPage: $perPage,
        );
    }

    public function toArray(): array
    {
        return [
            'data'     => $this->customers,
            'total'    => $this->total,
            'page'     => $this->page,
            'per_page' => $this->perPage,
            'pages'    => $this->perPage > 0 ? (int) ceil($this->total / $this->perPage) : 1,
        ];
    }
}
