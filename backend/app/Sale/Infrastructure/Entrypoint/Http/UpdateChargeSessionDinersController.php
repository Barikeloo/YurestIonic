<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\UpdateChargeSessionDiners\UpdateChargeSessionDiners;
use App\Sale\Domain\Exception\ChargeSessionNotFoundException;
use App\Sale\Domain\Exception\InvalidDinerCountException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\UpdateChargeSessionDinersRequest;
use Illuminate\Http\JsonResponse;

final class UpdateChargeSessionDinersController
{
    public function __construct(
        private readonly UpdateChargeSessionDiners $updateDiners,
    ) {}

    public function __invoke(UpdateChargeSessionDinersRequest $request): JsonResponse
    {
        try {
            $response = ($this->updateDiners)($request->toCommand());
        } catch (ChargeSessionNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (InvalidDinerCountException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
