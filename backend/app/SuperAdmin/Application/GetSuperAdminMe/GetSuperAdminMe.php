<?php

namespace App\SuperAdmin\Application\GetSuperAdminMe;

use App\SuperAdmin\Domain\Interfaces\SuperAdminRepositoryInterface;

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

        $superAdmin = $this->superAdminRepository->findById($superAdminId);

        if ($superAdmin === null) {
            return GetSuperAdminMeResponse::notAuthenticated();
        }

        return GetSuperAdminMeResponse::success(
            $superAdmin->id(),
            $superAdmin->name(),
            $superAdmin->email(),
        );
    }
}
