<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\CreateChargeSession\CreateChargeSession;
use App\Sale\Domain\Exception\InvalidDinerCountException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\CreateChargeSessionRequest;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;

final class CreateChargeSessionController
{
    public function __construct(
        private readonly CreateChargeSession $createChargeSession,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(CreateChargeSessionRequest $request): JsonResponse
    {
        $restaurantId = $this->tenantContext->restaurantUuid();
        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        try {
            $response = ($this->createChargeSession)($request->toCommand($restaurantId));
        } catch (InvalidDinerCountException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
