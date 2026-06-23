<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\ListCustomerAccounts;

final readonly class ListCustomerAccountsCommand
{
    public function __construct(
        public string $restaurantId,
        public ?string $search = null,
        public int $perPage = 20,
        public int $page = 1,
    ) {}
}
