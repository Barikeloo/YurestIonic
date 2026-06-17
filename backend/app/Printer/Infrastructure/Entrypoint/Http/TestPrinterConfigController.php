<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Entrypoint\Http;

use App\Printer\Application\TestPrinterConfig\TestPrinterConfig;
use App\Printer\Application\TestPrinterConfig\TestPrinterConfigCommand;
use App\Printer\Domain\Exception\PrinterConfigNotFoundException;
use App\Printer\Domain\Exception\PrinterConnectionException;
use Illuminate\Http\JsonResponse;

final class TestPrinterConfigController
{
    public function __construct(
        private readonly TestPrinterConfig $testPrinterConfig,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            ($this->testPrinterConfig)(new TestPrinterConfigCommand($id));
        } catch (PrinterConfigNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (PrinterConnectionException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse(['message' => 'Test page sent.']);
    }
}
