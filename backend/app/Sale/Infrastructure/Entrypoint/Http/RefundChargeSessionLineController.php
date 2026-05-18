<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\RefundChargeSessionLine\RefundChargeSessionLine;
use App\Sale\Domain\Exception\ChargeSessionNotFoundException;
use App\Sale\Domain\Exception\RefundablePaidLineNotFoundException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\RefundChargeSessionLineRequest;
use Illuminate\Http\JsonResponse;

final class RefundChargeSessionLineController
{
    public function __construct(
        private readonly RefundChargeSessionLine $useCase,
    ) {}

    public function __invoke(RefundChargeSessionLineRequest $request): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand());
        } catch (ChargeSessionNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (RefundablePaidLineNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
