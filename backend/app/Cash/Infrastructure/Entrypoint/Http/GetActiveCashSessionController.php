<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\GetActiveCashSession\GetActiveCashSession;
use App\Cash\Infrastructure\Entrypoint\Http\Requests\GetActiveCashSessionRequest;
use Illuminate\Http\JsonResponse;

final class GetActiveCashSessionController
{
    public function __construct(
        private readonly GetActiveCashSession $getActiveCashSession,
    ) {}

    public function __invoke(GetActiveCashSessionRequest $request): JsonResponse
    {
        try {
            $response = ($this->getActiveCashSession)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        if ($response === null) {
            return new JsonResponse(null, 204);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
