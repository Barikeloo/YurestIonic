<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\GetAdminRestaurantCollection\GetAdminRestaurantCollection;
use App\Restaurant\Application\GetAdminRestaurantCollection\GetAdminRestaurantCollectionResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminGetCollectionController
{
    public function __construct(
        private GetAdminRestaurantCollection $getAdminRestaurantCollection,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $superAdminUuid = $request->session()->get('super_admin_id');
        $authUserUuid = $request->session()->get('auth_user_id');

        $response = $this->getAdminRestaurantCollection->__invoke(
            is_string($authUserUuid) ? $authUserUuid : null,
            is_string($superAdminUuid) && $superAdminUuid !== '',
        );

        if ($response->status() === GetAdminRestaurantCollectionResponse::NOT_AUTHENTICATED) {
            return new JsonResponse([
                'message' => 'Not authenticated.',
            ], 401);
        }

        if ($response->status() === GetAdminRestaurantCollectionResponse::LINKED_RESTAURANT_NOT_FOUND) {
            return new JsonResponse([
                'message' => 'Linked restaurant not found.',
            ], 404);
        }

        if ($response->status() === GetAdminRestaurantCollectionResponse::LINKED_RESTAURANT_WITHOUT_TAX_ID) {
            return new JsonResponse([
                'message' => 'Linked restaurant has no tax id.',
            ], 422);
        }

        return new JsonResponse([
            'data' => $response->data(),
        ]);
    }
}
