<?php

namespace App\User\Infrastructure\Entrypoint\Http\Admin;

use App\User\Application\AuthorizeRestaurantAccess\AuthorizeRestaurantAccess;
use App\User\Application\AuthorizeRestaurantAccess\AuthorizeRestaurantAccessResponse;
use App\User\Application\UpdateRestaurantUser\UpdateRestaurantUser;
use App\User\Domain\ValueObject\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPutController
{
    public function __construct(
        private AuthorizeRestaurantAccess $authorizeRestaurantAccess,
        private UpdateRestaurantUser $updateRestaurantUser,
    ) {}

    public function __invoke(Request $request, string $uuid, string $userUuid): JsonResponse
    {
        $superAdminUuid = $request->session()->get('super_admin_id');
        $authUserUuid = null;

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
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255'],
            'password' => ['sometimes', 'string', 'min:8'],
            'role' => ['sometimes', 'string', 'in:operator,supervisor,admin'],
            'pin' => ['sometimes', 'nullable', 'digits:4'],
        ]);

        if (
            is_string($authUserUuid)
            && $authUserUuid === $userUuid
            && isset($validated['role'])
            && ! Role::create($validated['role'])->isAdmin()
        ) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No puedes cambiar tu propio rol de administrador.',
            ], 422);
        }

        $response = ($this->updateRestaurantUser)(
            $uuid,
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
