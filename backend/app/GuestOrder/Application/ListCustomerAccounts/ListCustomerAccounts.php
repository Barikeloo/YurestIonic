<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\ListCustomerAccounts;

use App\GuestOrder\Domain\Interfaces\LoyaltyReadRepositoryInterface;

final class ListCustomerAccounts
{
    public function __construct(
        private readonly LoyaltyReadRepositoryInterface $loyaltyReadRepository,
    ) {}

    public function __invoke(ListCustomerAccountsCommand $command): ListCustomerAccountsResponse
    {
        $customers = $this->loyaltyReadRepository->listCustomers(
            $command->restaurantId,
            $command->search,
            $command->perPage,
            $command->page,
        );

        $total = $this->loyaltyReadRepository->countCustomers(
            $command->restaurantId,
            $command->search,
        );

        return ListCustomerAccountsResponse::create($customers, $total, $command->page, $command->perPage);
    }
}
