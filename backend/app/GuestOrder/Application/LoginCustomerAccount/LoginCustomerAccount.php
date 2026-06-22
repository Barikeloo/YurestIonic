<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\LoginCustomerAccount;

use App\GuestOrder\Domain\Exception\InvalidCredentialsException;
use App\GuestOrder\Domain\Exception\TableQrTokenNotFoundException;
use App\GuestOrder\Domain\Interfaces\CustomerAccountRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;

final class LoginCustomerAccount
{
    public function __construct(
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
        private readonly CustomerAccountRepositoryInterface $customerAccountRepository,
    ) {}

    public function __invoke(LoginCustomerAccountCommand $command): LoginCustomerAccountResponse
    {
        $qrToken = $this->tableQrTokenRepository->findByToken($command->token)
            ?? throw TableQrTokenNotFoundException::withToken($command->token);

        $restaurantId = $qrToken->restaurantId()->value();
        $email        = strtolower(trim($command->email));

        $account = $this->customerAccountRepository->findByEmailAndRestaurant($email, $restaurantId)
            ?? throw InvalidCredentialsException::create();

        if (! $account->verifyPassword($command->password)) {
            throw InvalidCredentialsException::create();
        }

        $authToken = $account->generateAuthToken();
        $expiresAt = new \DateTimeImmutable('+15 minutes');
        $this->customerAccountRepository->saveAuthToken($account->id()->value(), $authToken, $expiresAt);

        $offers = $this->customerAccountRepository->getActiveOffers($restaurantId, $account->points());

        return LoginCustomerAccountResponse::create(
            customerId: $account->id()->value(),
            name: $account->name(),
            email: $account->email(),
            points: $account->points(),
            visitsCount: $account->visitsCount(),
            lastVisitAt: $account->lastVisitAt()?->format(\DateTimeInterface::ATOM),
            activeOffers: $offers,
            customerAuthToken: $authToken,
        );
    }
}
