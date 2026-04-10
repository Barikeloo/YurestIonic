<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetQuickUsers\GetQuickUsers;
use App\User\Application\GetQuickUsers\GetQuickUsersResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetQuickUsersController
{
    public function __construct(
        private GetQuickUsers $getQuickUsers,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:100'],
            'restaurant_uuid' => ['nullable', 'string', 'uuid'],
        ]);

        /** @var GetQuickUsersResponse $response */
        $response = ($this->getQuickUsers)($validated['device_id'], $validated['restaurant_uuid'] ?? null);

        return new JsonResponse($response->toArray());
    }
}
