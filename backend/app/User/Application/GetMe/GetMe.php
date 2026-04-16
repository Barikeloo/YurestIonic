<?php

namespace App\User\Application\GetMe;

use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Application\GetMe\GetMeResponse;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;

class GetMe
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(string $userId): ?GetMeResponse
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            return null;
        }

        $role = $user->role()?->value();
        $restaurantId = $user->restaurantId()?->toInt();
        $restaurantUuid = null;
        $restaurantName = null;


        if ($restaurantId !== null) {
            // Usar el repositorio para obtener el restaurante por id interno
            $restaurant = $this->restaurantRepository->findByInternalId($restaurantId);
            if ($restaurant !== null) {
                $restaurantUuid = $restaurant->uuid()->value();
                $restaurantName = $restaurant->name()->value();
            }
        }

        return GetMeResponse::create($user, $role, $restaurantUuid, $restaurantName);
    }
}