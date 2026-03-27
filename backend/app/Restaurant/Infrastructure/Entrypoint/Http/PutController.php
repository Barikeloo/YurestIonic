<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\UpdateRestaurant\UpdateRestaurant;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\SuperAdmin\Infrastructure\Persistence\Models\EloquentSuperAdmin;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PutController
{
    public function __construct(
        private readonly UpdateRestaurant $updateRestaurant,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $superAdminUuid = $request->session()->get('super_admin_id');
        $isSuperAdmin = is_string($superAdminUuid)
            && $superAdminUuid !== ''
            && EloquentSuperAdmin::query()->where('uuid', $superAdminUuid)->exists();

        if (! $isSuperAdmin) {
            $authUserUuid = $request->session()->get('auth_user_id');

            if (! is_string($authUserUuid) || $authUserUuid === '') {
                return new JsonResponse([
                    'message' => 'Not authenticated.',
                ], 401);
            }

            $user = EloquentUser::query()->where('uuid', $authUserUuid)->first();

            if ($user === null || $user->role !== 'admin' || ! is_numeric($user->restaurant_id)) {
                return new JsonResponse([
                    'message' => 'Forbidden.',
                ], 403);
            }

            $linkedRestaurant = EloquentRestaurant::query()->where('id', (int) $user->restaurant_id)->first();
            $targetRestaurant = EloquentRestaurant::query()->where('uuid', $id)->first();

            if ($linkedRestaurant === null || $targetRestaurant === null) {
                return new JsonResponse([
                    'message' => 'Restaurant not found.',
                ], 404);
            }

            if (! is_string($linkedRestaurant->tax_id) || $linkedRestaurant->tax_id === '') {
                return new JsonResponse([
                    'message' => 'Linked restaurant has no tax id.',
                ], 422);
            }

            if ($targetRestaurant->tax_id !== $linkedRestaurant->tax_id) {
                return new JsonResponse([
                    'message' => 'Forbidden for this restaurant.',
                ], 403);
            }
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'legal_name' => ['sometimes', 'nullable', 'string', 'min:1', 'max:255'],
            'tax_id' => ['sometimes', 'nullable', 'string', 'min:1', 'max:255'],
            'email' => ['sometimes', 'string', 'email'],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        $response = ($this->updateRestaurant)(
            id: $id,
            name: $validated['name'] ?? null,
            legalName: $validated['legal_name'] ?? null,
            taxId: $validated['tax_id'] ?? null,
            email: $validated['email'] ?? null,
            plainPassword: $validated['password'] ?? null,
        );

        if ($response === null) {
            return new JsonResponse(['message' => 'Restaurant not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
