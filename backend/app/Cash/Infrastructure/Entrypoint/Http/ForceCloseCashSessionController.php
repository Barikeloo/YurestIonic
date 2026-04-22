<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\ForceCloseCashSession\ForceCloseCashSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ForceCloseCashSessionController
{
    public function __construct(
        private readonly ForceCloseCashSession $forceCloseCashSession,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cash_session_id' => ['required', 'string', 'uuid'],
            'closed_by_user_id' => ['required', 'string', 'uuid'],
        ]);

        ($this->forceCloseCashSession)(
            cashSessionId: $validated['cash_session_id'],
            closedByUserId: $validated['closed_by_user_id'],
        );

        return new JsonResponse(['message' => 'Session force closed'], 200);
    }
}
