<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\CancelClosingCashSession\CancelClosingCashSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CancelClosingCashSessionController
{
    public function __construct(
        private readonly CancelClosingCashSession $cancelClosingCashSession,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cash_session_id' => ['required', 'string', 'uuid'],
        ]);

        $response = ($this->cancelClosingCashSession)(
            cashSessionId: $validated['cash_session_id'],
        );

        return new JsonResponse($response->toArray(), 200);
    }
}
