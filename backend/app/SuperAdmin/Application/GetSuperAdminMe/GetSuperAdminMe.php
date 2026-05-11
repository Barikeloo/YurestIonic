<?php

namespace App\SuperAdmin\Application\GetSuperAdminMe;

use App\Shared\Domain\ValueObject\Uuid;
use App\SuperAdmin\Domain\Exception\SuperAdminNotAuthenticatedException;
use App\SuperAdmin\Domain\Interfaces\SuperAdminRepositoryInterface;
use InvalidArgumentException;

class GetSuperAdminMe
{
    public function __construct(
        private SuperAdminRepositoryInterface $superAdminRepository,
    ) {}

    public function __invoke(GetSuperAdminMeCommand $command): GetSuperAdminMeResponse
    {
        if (! is_string($command->superAdminId) || $command->superAdminId === '') {
            throw SuperAdminNotAuthenticatedException::create();
        }

        try {
            $superAdmin = $this->superAdminRepository->findById(Uuid::create($command->superAdminId));
        } catch (InvalidArgumentException) {
            throw SuperAdminNotAuthenticatedException::create();
        }

        if ($superAdmin === null) {
            throw SuperAdminNotAuthenticatedException::create();
        }

        return GetSuperAdminMeResponse::create(
            id: $superAdmin->id()->value(),
            name: $superAdmin->name()->value(),
            email: $superAdmin->email()->value(),
        );
    }
}
