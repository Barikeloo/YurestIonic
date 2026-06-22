<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public;

use App\GuestOrder\Application\GetCatalogVersion\GetCatalogVersion;
use App\GuestOrder\Application\GetCatalogVersion\GetCatalogVersionCommand;
use App\GuestOrder\Domain\Exception\TableQrTokenNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetCatalogVersionController
{
    public function __construct(
        private readonly GetCatalogVersion $getCatalogVersion,
    ) {}

    public function __invoke(Request $request, string $token): JsonResponse
    {
        try {
            $response = ($this->getCatalogVersion)(new GetCatalogVersionCommand(token: $token));
        } catch (TableQrTokenNotFoundException $e) {
            return new JsonResponse(['error' => ['code' => 'QR_TOKEN_NOT_FOUND', 'message' => $e->getMessage()]], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Internal error.']], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
