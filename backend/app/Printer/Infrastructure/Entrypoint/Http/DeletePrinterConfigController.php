<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Entrypoint\Http;

use App\Printer\Application\DeletePrinterConfig\DeletePrinterConfig;
use App\Printer\Application\DeletePrinterConfig\DeletePrinterConfigCommand;
use App\Printer\Domain\Exception\PrinterConfigNotFoundException;
use Illuminate\Http\JsonResponse;

final class DeletePrinterConfigController
{
    public function __construct(
        private readonly DeletePrinterConfig $deletePrinterConfig,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            ($this->deletePrinterConfig)(new DeletePrinterConfigCommand($id));
        } catch (PrinterConfigNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse(null, 204);
    }
}
