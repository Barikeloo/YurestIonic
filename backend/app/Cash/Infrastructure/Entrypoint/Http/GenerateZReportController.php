<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\GenerateZReport\GenerateZReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GenerateZReportController
{
    public function __construct(
        private readonly GenerateZReport $generateZReport,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cash_session_id' => ['required', 'string', 'uuid'],
        ]);

        $response = ($this->generateZReport)($validated['cash_session_id']);

        return new JsonResponse($response->toArray(), 201);
    }
}
