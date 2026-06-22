<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetCatalogVersion;

use App\GuestOrder\Domain\Exception\TableQrTokenNotFoundException;
use App\GuestOrder\Domain\Interfaces\GuestCatalogRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;

final class GetCatalogVersion
{
    public function __construct(
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
        private readonly GuestCatalogRepositoryInterface $catalogRepository,
    ) {}

    public function __invoke(GetCatalogVersionCommand $command): GetCatalogVersionResponse
    {
        $qrToken = $this->tableQrTokenRepository->findByToken($command->token)
            ?? throw TableQrTokenNotFoundException::withToken($command->token);

        $restaurantInternalId = (int) EloquentRestaurant::query()
            ->where('uuid', $qrToken->restaurantId()->value())
            ->value('id');

        return GetCatalogVersionResponse::create(
            $this->catalogRepository->getCatalogVersion($restaurantInternalId),
        );
    }
}
