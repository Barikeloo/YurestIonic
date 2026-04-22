<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\StartClosingCashSession\StartClosingCashSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StartClosingCashSessionController
{
    public function __construct(
        private readonly StartClosingCashSession $startClosingCashSession,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cash_session_id' => ['required', 'string', 'uuid'],
        ]);

        ($this->startClosingCashSession)(
            cashSessionId: $validated['cash_session_id'],
        );

        return new JsonResponse(['message' => 'Closing started'], 200);
    }
}
