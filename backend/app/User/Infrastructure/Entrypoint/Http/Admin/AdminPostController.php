<?php

namespace App\User\Infrastructure\Entrypoint\Http\Admin;

use App\User\Application\CreateRestaurantUser\CreateRestaurantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPostController
{
    public function __construct(
        private CreateRestaurantUser $createRestaurantUser,
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

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $response = ($this->createRestaurantUser)(
            $validated['name'],
            $validated['email'],
            $validated['password'],
            $uuid,
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
