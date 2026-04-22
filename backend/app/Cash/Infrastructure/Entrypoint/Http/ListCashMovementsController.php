<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\ListCashMovements\ListCashMovements;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListCashMovementsController
{
    public function __construct(private ListCashMovements $listCashMovements) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cash_session_id' => ['required', 'string', 'uuid'],
        ]);

        $response = ($this->listCashMovements)($validated['cash_session_id']);

        return new JsonResponse($response->toArray());
    }
}
