<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetTableStatus;

use App\GuestOrder\Domain\Exception\TableQrTokenNotFoundException;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;

final class GetTableStatus
{
    public function __construct(
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
    ) {}

    public function __invoke(GetTableStatusCommand $command): GetTableStatusResponse
    {
        $statusData = $this->tableQrTokenRepository->findStatusByToken($command->token)
            ?? throw TableQrTokenNotFoundException::withToken($command->token);

        return GetTableStatusResponse::fromReadModel($statusData);
    }
}
