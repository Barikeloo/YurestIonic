<?php

namespace App\User\Application\CreateUser;

use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;

class CreateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(CreateUserCommand $command): CreateUserResponse
    {
        $emailVO = Email::create($command->email);
        $nameVO = UserName::create($command->name);
        $passwordHashVO = PasswordHash::create($this->passwordHasher->hash($command->plainPassword));
        $user = User::dddCreate($emailVO, $nameVO, $passwordHashVO);
        $this->userRepository->save($user);

        return new CreateUserResponse(
            id: $user->id()->value(),
            name: $user->name()->value(),
            email: $user->email()->value(),
            createdAt: $user->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $user->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
