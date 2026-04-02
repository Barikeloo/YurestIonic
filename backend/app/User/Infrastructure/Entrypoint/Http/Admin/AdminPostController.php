<?php

namespace App\User\Infrastructure\Entrypoint\Http\Admin;

use App\User\Application\AuthorizeRestaurantAccess\AuthorizeRestaurantAccess;
use App\User\Application\AuthorizeRestaurantAccess\AuthorizeRestaurantAccessResponse;
use App\User\Application\CreateRestaurantUser\CreateRestaurantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPostController
{
    public function __construct(
        private AuthorizeRestaurantAccess $authorizeRestaurantAccess,
        private CreateRestaurantUser $createRestaurantUser,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $superAdminUuid = $request->session()->get('super_admin_id');

        if (! is_string($superAdminUuid) || $superAdminUuid === '') {
            $authUserUuid = $request->session()->get('auth_user_id');

            if (! is_string($authUserUuid) || $authUserUuid === '') {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Not authenticated.',
                ], 401);
            }

            $authorization = $this->authorizeRestaurantAccess->__invoke($authUserUuid, $uuid);

            if ($authorization->status() === AuthorizeRestaurantAccessResponse::NOT_AUTHENTICATED) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Not authenticated.',
                ], 401);
            }

            if ($authorization->status() === AuthorizeRestaurantAccessResponse::RESTAURANT_NOT_FOUND) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Restaurant not found.',
                ], 404);
            }

            if ($authorization->status() === AuthorizeRestaurantAccessResponse::FORBIDDEN) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Forbidden.',
                ], 403);
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['sometimes', 'string', 'in:operator,supervisor,admin'],
            'pin' => ['sometimes', 'nullable', 'digits:4'],
        ]);

        $response = ($this->createRestaurantUser)(
            $validated['name'],
            $validated['email'],
            $validated['password'],
            $uuid,
            $validated['role'] ?? 'operator',
            $validated['pin'] ?? null,
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
