<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Entrypoint\Http;

use App\Printer\Application\PrintPreTicket\PrintPreTicket;
use App\Printer\Application\PrintPreTicket\PrintPreTicketCommand;
use App\Printer\Domain\Exception\PrinterConnectionException;
use Illuminate\Http\JsonResponse;

final class PrintPreTicketController
{
    public function __construct(
        private readonly PrintPreTicket $printPreTicket,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            ($this->printPreTicket)(new PrintPreTicketCommand($id));
        } catch (PrinterConnectionException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse(['message' => 'Pre-ticket sent to printer.']);
    }
}
