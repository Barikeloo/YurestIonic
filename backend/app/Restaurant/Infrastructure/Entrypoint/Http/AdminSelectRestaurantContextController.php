<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\SelectRestaurantContext\SelectRestaurantContext;
use App\Restaurant\Application\SelectRestaurantContext\SelectRestaurantContextResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminSelectRestaurantContextController
{
    public function __construct(
        private SelectRestaurantContext $selectRestaurantContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'string', 'uuid'],
        ]);

        $superAdminUuid = $request->session()->get('super_admin_id');
        $authUserUuid = $request->session()->get('auth_user_id');

        $response = $this->selectRestaurantContext->__invoke(
            is_string($authUserUuid) ? $authUserUuid : null,
            $validated['restaurant_id'],
            is_string($superAdminUuid) && $superAdminUuid !== '',
        );

        if ($response->status() === SelectRestaurantContextResponse::RESTAURANT_NOT_FOUND) {
            return new JsonResponse([
                'message' => 'Restaurant not found.',
            ], 404);
        }

        if ($response->status() === SelectRestaurantContextResponse::NOT_AUTHENTICATED) {
            return new JsonResponse([
                'message' => 'Not authenticated.',
            ], 401);
        }

        if ($response->status() === SelectRestaurantContextResponse::LINKED_RESTAURANT_NOT_FOUND) {
            return new JsonResponse([
                'message' => 'Linked restaurant not found.',
            ], 404);
        }

        if ($response->status() === SelectRestaurantContextResponse::LINKED_RESTAURANT_WITHOUT_TAX_ID) {
            return new JsonResponse([
                'message' => 'Linked restaurant has no tax id.',
            ], 422);
        }

        if ($response->status() === SelectRestaurantContextResponse::FORBIDDEN) {
            return new JsonResponse([
                'message' => 'Forbidden for this restaurant context.',
            ], 403);
        }

        $request->session()->put('tenant_restaurant_uuid', $response->restaurantUuid());

        return new JsonResponse([
            'success' => true,
            'restaurant_id' => $response->restaurantUuid(),
            'name' => $response->restaurantName(),
        ]);
    }
}
