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
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255'],
            'password' => ['sometimes', 'string', 'min:8'],
            'role' => ['sometimes', 'string', 'in:operator,supervisor,admin'],
            'pin' => ['sometimes', 'nullable', 'digits:4'],
        ]);

        $response = ($this->updateRestaurantUser)(
            $userUuid,
            $validated['name'] ?? null,
            $validated['email'] ?? null,
            $validated['password'] ?? null,
            $validated['role'] ?? null,
            $validated['pin'] ?? null,
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
