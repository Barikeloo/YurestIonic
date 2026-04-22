<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\GetActiveCashSession\GetActiveCashSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetActiveCashSessionController
{
    public function __construct(
        private readonly GetActiveCashSession $getActiveCashSession,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'string', 'uuid'],
            'device_id' => ['required', 'string', 'max:100'],
        ]);

        $response = ($this->getActiveCashSession)(
            restaurantId: $validated['restaurant_id'],
            deviceId: $validated['device_id'],
        );

        if ($response === null) {
            return new JsonResponse(null, 204);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
