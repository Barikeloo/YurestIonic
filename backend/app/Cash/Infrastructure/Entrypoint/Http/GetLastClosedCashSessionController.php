<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\GetLastClosedCashSession\GetLastClosedCashSession;
use App\Cash\Infrastructure\Entrypoint\Http\Requests\GetLastClosedCashSessionRequest;
use Illuminate\Http\JsonResponse;

final class GetLastClosedCashSessionController
{
    public function __construct(
        private readonly GetLastClosedCashSession $getLastClosedCashSession,
    ) {}

    public function __invoke(GetLastClosedCashSessionRequest $request): JsonResponse
    {
        try {
            $response = ($this->getLastClosedCashSession)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
