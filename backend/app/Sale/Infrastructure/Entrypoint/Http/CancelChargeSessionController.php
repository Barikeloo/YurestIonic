<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\CancelChargeSession\CancelChargeSession;
use App\Sale\Domain\Exception\ChargeSessionNotActiveException;
use App\Sale\Domain\Exception\ChargeSessionNotFoundException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\CancelChargeSessionRequest;
use Illuminate\Http\JsonResponse;

final class CancelChargeSessionController
{
    public function __construct(
        private readonly CancelChargeSession $cancelChargeSession,
    ) {}

    public function __invoke(CancelChargeSessionRequest $request): JsonResponse
    {
        try {
            $response = ($this->cancelChargeSession)($request->toCommand());
        } catch (ChargeSessionNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (ChargeSessionNotActiveException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
