<?php

namespace App\User\Infrastructure\Entrypoint\Http\Admin;

use App\User\Application\GetRestaurantUsers\GetRestaurantUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminGetCollectionController
{
    public function __construct(
        private GetRestaurantUsers $getRestaurantUsers,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $response = ($this->getRestaurantUsers)($uuid);

        return new JsonResponse($response->toArray(), 200);
    }
}
