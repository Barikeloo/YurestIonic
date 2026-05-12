<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\RegisterCashMovement\RegisterCashMovement;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Exception\CashSessionNotOpenForMovementException;
use App\Cash\Infrastructure\Entrypoint\Http\Requests\RegisterCashMovementRequest;
use Illuminate\Http\JsonResponse;

final class RegisterCashMovementController
{
    public function __construct(
        private readonly RegisterCashMovement $registerCashMovement,
    ) {}

    public function __invoke(RegisterCashMovementRequest $request): JsonResponse
    {
        try {
            $response = ($this->registerCashMovement)($request->toCommand());
        } catch (CashSessionNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (CashSessionNotOpenForMovementException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
