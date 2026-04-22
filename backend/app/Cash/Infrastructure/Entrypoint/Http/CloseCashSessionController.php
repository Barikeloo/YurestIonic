<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\CloseCashSession\CloseCashSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CloseCashSessionController
{
    public function __construct(
        private readonly CloseCashSession $closeCashSession,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cash_session_id' => ['required', 'string', 'uuid'],
            'closed_by_user_id' => ['required', 'string', 'uuid'],
            'final_amount_cents' => ['required', 'integer', 'min:0'],
            'expected_amount_cents' => ['required', 'integer', 'min:0'],
            'discrepancy_cents' => ['required', 'integer'],
            'discrepancy_reason' => ['nullable', 'string'],
        ]);

        $response = ($this->closeCashSession)(
            cashSessionId: $validated['cash_session_id'],
            closedByUserId: $validated['closed_by_user_id'],
            finalAmountCents: $validated['final_amount_cents'],
            expectedAmountCents: $validated['expected_amount_cents'],
            discrepancyCents: $validated['discrepancy_cents'],
            discrepancyReason: $validated['discrepancy_reason'] ?? null,
        );

        return new JsonResponse($response->toArray(), 200);
    }
}
