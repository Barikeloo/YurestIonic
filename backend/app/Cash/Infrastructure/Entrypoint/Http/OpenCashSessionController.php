<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\OpenCashSession\OpenCashSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OpenCashSessionController
{
    public function __construct(
        private readonly OpenCashSession $openCashSession,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'string', 'uuid'],
            'device_id' => ['required', 'string', 'max:100'],
            'opened_by_user_id' => ['required', 'string', 'uuid'],
            'initial_amount_cents' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $response = ($this->openCashSession)(
            restaurantId: $validated['restaurant_id'],
            deviceId: $validated['device_id'],
            openedByUserId: $validated['opened_by_user_id'],
            initialAmountCents: $validated['initial_amount_cents'],
            notes: $validated['notes'] ?? null,
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
