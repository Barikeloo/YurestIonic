<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\GetCashSessionSummary\GetCashSessionSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetCashSessionSummaryController
{
    public function __construct(
        private readonly GetCashSessionSummary $getCashSessionSummary,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $response = ($this->getCashSessionSummary)(
            cashSessionId: $id,
        );

        if ($response === null) {
            return new JsonResponse(null, 204);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
