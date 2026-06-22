<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetCatalogForGuest;

use App\GuestOrder\Domain\Exception\TableQrTokenNotFoundException;
use App\GuestOrder\Domain\Interfaces\GuestCatalogRepositoryInterface;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;

final class GetCatalogForGuest
{
    public function __construct(
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
        private readonly GuestCatalogRepositoryInterface $catalogRepository,
    ) {}

    public function __invoke(GetCatalogForGuestCommand $command): GetCatalogForGuestResponse
    {
        $qrToken = $this->tableQrTokenRepository->findByToken($command->token)
            ?? throw TableQrTokenNotFoundException::withToken($command->token);

        $restaurantInternalId = (int) EloquentRestaurant::query()
            ->where('uuid', $qrToken->restaurantId()->value())
            ->value('id');

        $catalog = $this->catalogRepository->getCatalog(
            restaurantInternalId: $restaurantInternalId,
            catalogVersion: $qrToken->catalogVersion(),
        );

        return GetCatalogForGuestResponse::fromReadModel($catalog);
    }
}
