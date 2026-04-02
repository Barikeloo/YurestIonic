<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\AuthorizeRestaurantUpdate\AuthorizeRestaurantUpdate;
use App\Restaurant\Application\AuthorizeRestaurantUpdate\AuthorizeRestaurantUpdateResponse;
use App\Restaurant\Application\UpdateRestaurant\UpdateRestaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PutController
{
    public function __construct(
        private readonly AuthorizeRestaurantUpdate $authorizeRestaurantUpdate,
        private readonly UpdateRestaurant $updateRestaurant,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $superAdminUuid = $request->session()->get('super_admin_id');
        $isSuperAdmin = is_string($superAdminUuid)
            && $superAdminUuid !== '';

        if (! $isSuperAdmin) {
            $authUserUuid = $request->session()->get('auth_user_id');

            $authorization = $this->authorizeRestaurantUpdate->__invoke(
                is_string($authUserUuid) ? $authUserUuid : null,
                $id,
            );

            if ($authorization->status() === AuthorizeRestaurantUpdateResponse::NOT_AUTHENTICATED) {
                return new JsonResponse([
                    'message' => 'Not authenticated.',
                ], 401);
            }

            if ($authorization->status() === AuthorizeRestaurantUpdateResponse::FORBIDDEN) {
                return new JsonResponse([
                    'message' => 'Forbidden.',
                ], 403);
            }

            if ($authorization->status() === AuthorizeRestaurantUpdateResponse::RESTAURANT_NOT_FOUND) {
                return new JsonResponse([
                    'message' => 'Restaurant not found.',
                ], 404);
            }

            if ($authorization->status() === AuthorizeRestaurantUpdateResponse::LINKED_RESTAURANT_WITHOUT_TAX_ID) {
                return new JsonResponse([
                    'message' => 'Linked restaurant has no tax id.',
                ], 422);
            }
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'legal_name' => ['sometimes', 'nullable', 'string', 'min:1', 'max:255'],
            'tax_id' => ['sometimes', 'nullable', 'string', 'min:1', 'max:255'],
            'email' => ['sometimes', 'string', 'email'],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        if (! $isSuperAdmin && (array_key_exists('legal_name', $validated) || array_key_exists('tax_id', $validated))) {
            return new JsonResponse([
                'message' => 'Forbidden. Only superadmins can update legal data.',
            ], 403);
        }

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
