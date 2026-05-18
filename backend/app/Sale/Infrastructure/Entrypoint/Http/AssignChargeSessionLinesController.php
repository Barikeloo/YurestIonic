<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\AssignChargeSessionLines\AssignChargeSessionLines;
use App\Sale\Domain\Exception\ChargeSessionNotActiveException;
use App\Sale\Domain\Exception\ChargeSessionNotFoundException;
use App\Sale\Domain\Exception\InvalidDinerCountException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\AssignChargeSessionLinesRequest;
use Illuminate\Http\JsonResponse;

final class AssignChargeSessionLinesController
{
    public function __construct(
        private readonly AssignChargeSessionLines $useCase,
    ) {}

    public function __invoke(AssignChargeSessionLinesRequest $request): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand());
        } catch (ChargeSessionNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (ChargeSessionNotActiveException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (InvalidDinerCountException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
