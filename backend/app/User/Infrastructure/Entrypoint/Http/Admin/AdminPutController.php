<?php

namespace App\User\Infrastructure\Entrypoint\Http\Admin;

use App\User\Application\UpdateRestaurantUser\UpdateRestaurantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPutController
{
    public function __construct(
        private UpdateRestaurantUser $updateRestaurantUser,
    ) {}

    public function __invoke(Request $request, string $uuid, string $userUuid): JsonResponse
    {
        $userId = $request->session()->get('auth_user_id');

        if (! is_string($userId) || $userId === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255'],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        $response = ($this->updateRestaurantUser)(
            $userUuid,
            $validated['name'] ?? null,
            $validated['email'] ?? null,
            $validated['password'] ?? null,
        );

        if (! $response->found()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
