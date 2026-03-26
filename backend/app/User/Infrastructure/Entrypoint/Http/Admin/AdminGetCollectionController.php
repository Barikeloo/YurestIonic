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
        $userId = $request->session()->get('auth_user_id');

        if (! is_string($userId) || $userId === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        }

        $response = ($this->getRestaurantUsers)($uuid);

        return new JsonResponse($response->toArray(), 200);
    }
}
