<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Entrypoint\Http;

use App\Printer\Application\UpsertPrinterConfig\UpsertPrinterConfig;
use App\Printer\Domain\Exception\PrinterConfigNotFoundException;
use App\Printer\Infrastructure\Entrypoint\Http\Requests\UpsertPrinterConfigRequest;
use Illuminate\Http\JsonResponse;

final class UpdatePrinterConfigController
{
    public function __construct(
        private readonly UpsertPrinterConfig $upsertPrinterConfig,
    ) {}

    public function __invoke(UpsertPrinterConfigRequest $request, string $id): JsonResponse
    {
        try {
            $response = ($this->upsertPrinterConfig)($request->toCommand($id));
        } catch (PrinterConfigNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray());
    }
}
