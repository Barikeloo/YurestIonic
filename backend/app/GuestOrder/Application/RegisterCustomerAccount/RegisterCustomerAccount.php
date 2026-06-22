<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\RegisterCustomerAccount;

use App\GuestOrder\Domain\Entity\CustomerAccount;
use App\GuestOrder\Domain\Exception\EmailAlreadyRegisteredException;
use App\GuestOrder\Domain\Exception\TableQrTokenNotFoundException;
use App\GuestOrder\Domain\Interfaces\CustomerAccountRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;

final class RegisterCustomerAccount
{
    public function __construct(
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
        private readonly CustomerAccountRepositoryInterface $customerAccountRepository,
    ) {}

    public function __invoke(RegisterCustomerAccountCommand $command): RegisterCustomerAccountResponse
    {
        $qrToken = $this->tableQrTokenRepository->findByToken($command->token)
            ?? throw TableQrTokenNotFoundException::withToken($command->token);

        $restaurantId = $qrToken->restaurantId()->value();
        $email        = strtolower(trim($command->email));

        if ($this->customerAccountRepository->findByEmailAndRestaurant($email, $restaurantId) !== null) {
            throw EmailAlreadyRegisteredException::withEmail($email);
        }

        $passwordHash = password_hash($command->password, PASSWORD_BCRYPT);

        $account = CustomerAccount::dddCreate(
            restaurantId: $qrToken->restaurantId(),
            name: $command->name,
            email: $email,
            passwordHash: $passwordHash,
        );

        $this->customerAccountRepository->save($account);

        $authToken = $account->generateAuthToken();
        $expiresAt = new \DateTimeImmutable('+15 minutes');
        $this->customerAccountRepository->saveAuthToken($account->id()->value(), $authToken, $expiresAt);

        return RegisterCustomerAccountResponse::create(
            customerId: $account->id()->value(),
            name: $account->name(),
            email: $account->email(),
            points: $account->points(),
            visitsCount: $account->visitsCount(),
            customerAuthToken: $authToken,
        );
    }
}
