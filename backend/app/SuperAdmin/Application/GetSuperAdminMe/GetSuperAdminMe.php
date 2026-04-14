<?php

namespace App\SuperAdmin\Application\GetSuperAdminMe;

use App\Shared\Domain\ValueObject\Uuid;
use App\SuperAdmin\Domain\Interfaces\SuperAdminRepositoryInterface;
use InvalidArgumentException;

final class GetSuperAdminMe
{
    public function __construct(
        private SuperAdminRepositoryInterface $superAdminRepository,
    ) {}

    public function __invoke(?string $superAdminId): GetSuperAdminMeResponse
    {
        if (! is_string($superAdminId) || $superAdminId === '') {
            return GetSuperAdminMeResponse::notAuthenticated();
        }

        try {
            $superAdmin = $this->superAdminRepository->findById(Uuid::create($superAdminId));
        } catch (InvalidArgumentException) {
            return GetSuperAdminMeResponse::notAuthenticated();
        }

        if ($superAdmin === null) {
            return GetSuperAdminMeResponse::notAuthenticated();
        }

        return GetSuperAdminMeResponse::success(
            $superAdmin->id()->value(),
            $superAdmin->name()->value(),
            $superAdmin->email()->value(),
        );
    }
}
