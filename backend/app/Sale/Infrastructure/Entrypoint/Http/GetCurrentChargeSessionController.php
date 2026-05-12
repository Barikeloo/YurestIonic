<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetCurrentChargeSession\GetCurrentChargeSession;
use App\Sale\Domain\Exception\ChargeSessionNotFoundException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\GetCurrentChargeSessionRequest;
use Illuminate\Http\JsonResponse;

final class GetCurrentChargeSessionController
{
    public function __construct(
        private readonly GetCurrentChargeSession $getCurrentChargeSession,
    ) {}

    public function __invoke(GetCurrentChargeSessionRequest $request): JsonResponse
    {
        try {
            $response = ($this->getCurrentChargeSession)($request->toCommand());
        } catch (ChargeSessionNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
