<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\MarkOrderToCharge\MarkOrderToCharge;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Infrastructure\Entrypoint\Http\Requests\MarkOrderToChargeRequest;
use Illuminate\Http\JsonResponse;

final class MarkOrderToChargeController
{
    public function __construct(
        private readonly MarkOrderToCharge $markOrderToCharge,
    ) {}

    public function __invoke(MarkOrderToChargeRequest $request): JsonResponse
    {
        try {
            $response = ($this->markOrderToCharge)($request->toCommand());
        } catch (OrderNotFoundException $e) {
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
