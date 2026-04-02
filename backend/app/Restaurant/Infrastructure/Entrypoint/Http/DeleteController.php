<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\DeleteRestaurant\DeleteRestaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeleteController
{
    public function __construct(
        private readonly DeleteRestaurant $deleteRestaurant,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $superAdminUuid = $request->session()->get('super_admin_id');

        if (! is_string($superAdminUuid) || $superAdminUuid === '') {
            return new JsonResponse([
                'message' => 'Forbidden. Only superadmins can delete restaurants.',
            ], 403);
        }

        $deleted = ($this->deleteRestaurant)($id);

        if (! $deleted) {
            return new JsonResponse(['message' => 'Restaurant not found.'], 404);
        }

        return new JsonResponse(status: 204);
    }
}
