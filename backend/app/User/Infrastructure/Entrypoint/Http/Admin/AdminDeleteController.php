<?php

namespace App\User\Infrastructure\Entrypoint\Http\Admin;

use App\User\Application\DeleteRestaurantUser\DeleteRestaurantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDeleteController
{
    public function __construct(
        private DeleteRestaurantUser $deleteRestaurantUser,
    ) {}

    public function __invoke(Request $request, string $uuid, string $userUuid): JsonResponse
    {
        $response = ($this->deleteRestaurantUser)($userUuid);

        if (! $response->found()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
