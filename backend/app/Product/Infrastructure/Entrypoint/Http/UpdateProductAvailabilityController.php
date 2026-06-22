<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\UpdateProductAvailability\UpdateProductAvailability;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Infrastructure\Entrypoint\Http\Requests\UpdateProductAvailabilityRequest;
use Illuminate\Http\JsonResponse;

final class UpdateProductAvailabilityController
{
    public function __construct(
        private readonly UpdateProductAvailability $updateProductAvailability,
    ) {}

    public function __invoke(UpdateProductAvailabilityRequest $request): JsonResponse
    {
        try {
            $response = ($this->updateProductAvailability)($request->toCommand());
        } catch (ProductNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
