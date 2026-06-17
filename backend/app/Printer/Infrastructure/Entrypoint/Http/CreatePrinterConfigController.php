<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Entrypoint\Http;

use App\Printer\Application\UpsertPrinterConfig\UpsertPrinterConfig;
use App\Printer\Infrastructure\Entrypoint\Http\Requests\UpsertPrinterConfigRequest;
use Illuminate\Http\JsonResponse;

final class CreatePrinterConfigController
{
    public function __construct(
        private readonly UpsertPrinterConfig $upsertPrinterConfig,
    ) {}

    public function __invoke(UpsertPrinterConfigRequest $request): JsonResponse
    {
        try {
            $response = ($this->upsertPrinterConfig)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
